<?php

namespace CatFlow\Batch\Services;

use CatFlow\Batch\Models\Batch;
use CatFlow\Batch\Models\Output;
use CatFlow\Batch\OpenAi\BatchApiException;
use CatFlow\Batch\OpenAi\OpenAiBatchClient;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BatchStatusPoller
{
    private const TERMINAL_STATUSES = ['completed', 'failed', 'expired', 'cancelled'];

    public function __construct(
        private readonly OpenAiBatchClient $client,
    ) {
    }

    /**
     * Check a batch's remote status and bring the local row up to date.
     * Never throws — a polling hiccup (missing key, network error, OpenAI
     * outage) is reported and left for the next poll to retry; only
     * OpenAI's own terminal status may mark the batch failed/expired.
     */
    public function poll(Batch $batch): void
    {
        $apiKey = $batch->user->apiKey?->encrypted_key;

        if (! $apiKey) {
            report(BatchApiException::missingApiKey());

            return;
        }

        try {
            $remote = $this->client->retrieveBatch($apiKey, $batch->provider_job_id);
        } catch (Throwable $e) {
            report($e);

            return;
        }

        $status = OpenAiBatchClient::mapStatus($remote['status']);
        $counts = $remote['request_counts'] ?? [];

        $batch->update([
            'status' => $status,
            'request_total' => (int) ($counts['total'] ?? $batch->request_total ?? 0),
            'request_completed' => (int) ($counts['completed'] ?? $batch->request_completed ?? 0),
            'request_failed' => (int) ($counts['failed'] ?? $batch->request_failed ?? 0),
            'output_file_id' => $remote['output_file_id'] ?? $batch->output_file_id,
            'error_file_id' => $remote['error_file_id'] ?? $batch->error_file_id,
            'last_polled_at' => now(),
        ]);

        if ($status === 'completed') {
            $this->storeResultFiles($batch, $apiKey);
            $batch->update(['finished_at' => now()]);
        } elseif (in_array($status, ['failed', 'expired', 'cancelled'], true)) {
            $batch->update([
                'finished_at' => now(),
                'error_message' => $this->extractErrorMessage($remote),
            ]);
        }
    }

    public function isTerminal(Batch $batch): bool
    {
        return in_array($batch->status, self::TERMINAL_STATUSES, true);
    }

    /**
     * Poll only if it's actually due — lets a frequently-hit endpoint (the
     * live status page) piggyback on the same OpenAI-call budget as the
     * background job, instead of every page view/tick spending a real call.
     */
    public function pollIfStale(Batch $batch): void
    {
        if ($this->isTerminal($batch) || ! $batch->provider_job_id) {
            return;
        }

        $interval = (int) config('services.openai.batch_poll_interval_seconds');

        if ($batch->last_polled_at !== null && $batch->last_polled_at->gt(now()->subSeconds($interval))) {
            return;
        }

        $this->poll($batch);
    }

    /**
     * Download and store the raw output/error .jsonl once, the first time
     * a batch is seen completed. Re-polling an already-completed batch
     * (which shouldn't normally happen since the job stops requeuing once
     * terminal, but is cheap to guard against) must not re-download.
     */
    private function storeResultFiles(Batch $batch, string $apiKey): void
    {
        if ($batch->outputs()->exists()) {
            return;
        }

        if ($batch->output_file_id) {
            $this->downloadAndStore($batch, $apiKey, $batch->output_file_id, Output::TYPE_RAW_OUTPUT);
        }

        if ($batch->error_file_id) {
            $this->downloadAndStore($batch, $apiKey, $batch->error_file_id, Output::TYPE_RAW_ERROR);
        }
    }

    private function downloadAndStore(Batch $batch, string $apiKey, string $fileId, string $type): void
    {
        try {
            $content = $this->client->downloadFileContent($apiKey, $fileId);
        } catch (Throwable $e) {
            report($e);

            return;
        }

        $path = "datasets/{$batch->user_id}/batches/{$batch->id}-{$type}.jsonl";

        Storage::disk('local')->put($path, $content);

        Output::create([
            'batch_id' => $batch->id,
            'type' => $type,
            'storage_path' => $path,
        ]);
    }

    /**
     * @param  array<string, mixed>  $remote
     */
    private function extractErrorMessage(array $remote): ?string
    {
        $message = $remote['errors']['data'][0]['message'] ?? null;

        return is_string($message) ? $message : null;
    }
}
