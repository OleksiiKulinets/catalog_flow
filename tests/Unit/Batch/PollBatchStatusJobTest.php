<?php

namespace Tests\Unit\Batch;

use CatFlow\Batch\Jobs\PollBatchStatusJob;
use CatFlow\Batch\Models\Batch;
use CatFlow\Batch\Services\BatchStatusPoller;
use CatFlow\File\Models\Dataset;
use CatFlow\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PollBatchStatusJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function submittedBatch(): Batch
    {
        $user = User::factory()->create();

        $user->apiKey()->create([
            'provider' => 'openai',
            'encrypted_key' => 'sk-test-0123456789',
            'last_four' => '6789',
        ]);

        $dataset = Dataset::create([
            'user_id' => $user->id,
            'name' => 'products.csv',
            'source_type' => Dataset::SOURCE_UPLOAD,
            'source_format' => 'csv',
            'storage_path' => "datasets/{$user->id}/d.csv",
            'rows_count' => 1,
        ]);

        return Batch::create([
            'user_id' => $user->id,
            'dataset_id' => $dataset->id,
            'provider' => 'openai',
            'model' => 'gpt-4.1',
            'output_format' => 'csv',
            'prompt' => 'do something',
            'status' => 'queued',
            'provider_job_id' => 'batch-xyz',
        ]);
    }

    public function test_requeues_itself_when_the_batch_is_not_yet_terminal(): void
    {
        $batch = $this->submittedBatch();

        Http::fake(['api.openai.com/v1/batches/batch-xyz' => Http::response([
            'id' => 'batch-xyz',
            'status' => 'in_progress',
        ], 200)]);

        // The test suite runs on QUEUE_CONNECTION=sync, under which
        // self-requeuing is deliberately skipped (see PollBatchStatusJob) to
        // avoid recursing in-process — simulate a real queue connection for
        // this assertion, same as production's QUEUE_CONNECTION=database.
        config(['queue.default' => 'database']);
        Queue::fake();

        (new PollBatchStatusJob($batch))->handle(app(BatchStatusPoller::class));

        Queue::assertPushed(PollBatchStatusJob::class, 1);
    }

    public function test_does_not_requeue_once_the_batch_is_terminal(): void
    {
        $batch = $this->submittedBatch();

        Http::fake(['api.openai.com/v1/batches/batch-xyz' => Http::response([
            'id' => 'batch-xyz',
            'status' => 'completed',
        ], 200)]);

        Queue::fake();

        (new PollBatchStatusJob($batch))->handle(app(BatchStatusPoller::class));

        Queue::assertNotPushed(PollBatchStatusJob::class);
    }

    public function test_does_not_requeue_on_the_sync_queue_connection_even_when_not_terminal(): void
    {
        // Regression guard: the 'sync' connection ignores delay() and runs
        // the next dispatch immediately in-process, which would otherwise
        // recurse forever since this batch never reaches a terminal status.
        $batch = $this->submittedBatch();

        Http::fake(['api.openai.com/v1/batches/batch-xyz' => Http::response([
            'id' => 'batch-xyz',
            'status' => 'in_progress',
        ], 200)]);

        config(['queue.default' => 'sync']);
        Queue::fake();

        (new PollBatchStatusJob($batch))->handle(app(BatchStatusPoller::class));

        Queue::assertNotPushed(PollBatchStatusJob::class);
    }
}
