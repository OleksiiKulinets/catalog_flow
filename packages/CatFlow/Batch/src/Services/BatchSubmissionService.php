<?php

namespace CatFlow\Batch\Services;

use CatFlow\Batch\Models\Batch;
use CatFlow\Batch\OpenAi\BatchApiException;
use CatFlow\Batch\OpenAi\OpenAiBatchClient;
use Illuminate\Support\Facades\Storage;

class BatchSubmissionService
{
    public function __construct(
        private readonly OpenAiBatchClient $client,
    ) {
    }

    /**
     * Upload the batch's .jsonl file to OpenAI and create the Batch job.
     * Does not catch its own failures — same convention as
     * OpenAiAnalysisClient/SchemaAnalyzer: the caller decides what to do
     * with a failed submission.
     */
    public function submit(Batch $batch): void
    {
        $batch->update(['status' => 'uploading']);

        $apiKey = $batch->user->apiKey?->encrypted_key;

        if (! $apiKey) {
            throw BatchApiException::missingApiKey();
        }

        $fileId = $this->client->uploadFile($apiKey, Storage::disk('local')->path($batch->input_jsonl_path));

        $remote = $this->client->createBatch($apiKey, $fileId);

        $batch->update([
            'input_file_id' => $fileId,
            'provider_job_id' => $remote['id'],
            'status' => OpenAiBatchClient::mapStatus($remote['status']),
            'started_at' => now(),
        ]);
    }
}
