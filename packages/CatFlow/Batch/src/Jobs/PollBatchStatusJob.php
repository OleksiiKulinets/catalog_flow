<?php

namespace CatFlow\Batch\Jobs;

use CatFlow\Batch\Models\Batch;
use CatFlow\Batch\Services\BatchStatusPoller;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Self-requeuing poll: OpenAI batches can take minutes to 24h to finish, so
 * this checks status once and, if not yet terminal, dispatches itself again
 * after a delay rather than blocking a worker or requiring a scheduler.
 */
class PollBatchStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly Batch $batch)
    {
    }

    public function handle(BatchStatusPoller $poller): void
    {
        $poller->poll($this->batch);

        if ($poller->isTerminal($this->batch)) {
            return;
        }

        // The 'sync' queue connection ignores delay() and runs the next
        // dispatch immediately, in-process — self-requeuing under it would
        // recurse forever instead of actually waiting. Polling needs a real
        // queue connection; under 'sync' there's nothing safe to do here
        // but stop (the batch is still being processed by OpenAI regardless
        // — it just won't be polled again until something re-triggers it).
        if (config('queue.default') === 'sync') {
            return;
        }

        self::dispatch($this->batch)
            ->delay(now()->addSeconds((int) config('services.openai.batch_poll_interval_seconds')));
    }
}
