<?php

namespace Tests\Unit\Batch;

use CatFlow\Batch\Models\Batch;
use CatFlow\Batch\Models\Output;
use CatFlow\Batch\Services\BatchStatusPoller;
use CatFlow\File\Models\Dataset;
use CatFlow\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BatchStatusPollerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function submittedBatch(bool $withApiKey = true): Batch
    {
        $user = User::factory()->create();

        if ($withApiKey) {
            $user->apiKey()->create([
                'provider' => 'openai',
                'encrypted_key' => 'sk-test-0123456789',
                'last_four' => '6789',
            ]);
        }

        $dataset = Dataset::create([
            'user_id' => $user->id,
            'name' => 'products.csv',
            'source_type' => Dataset::SOURCE_UPLOAD,
            'source_format' => 'csv',
            'storage_path' => "datasets/{$user->id}/d.csv",
            'rows_count' => 2,
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

    public function test_a_non_terminal_status_just_updates_counts(): void
    {
        $batch = $this->submittedBatch();

        Http::fake(['api.openai.com/v1/batches/batch-xyz' => Http::response([
            'id' => 'batch-xyz',
            'status' => 'in_progress',
            'request_counts' => ['total' => 2, 'completed' => 1, 'failed' => 0],
        ], 200)]);

        app(BatchStatusPoller::class)->poll($batch);

        $this->assertSame('in_progress', $batch->status);
        $this->assertSame(1, $batch->request_completed);
        $this->assertNotNull($batch->last_polled_at);
        $this->assertDatabaseCount('outputs', 0);
    }

    public function test_completed_downloads_and_stores_the_raw_output_and_error_files(): void
    {
        $batch = $this->submittedBatch();

        Http::fake([
            'api.openai.com/v1/batches/batch-xyz' => Http::response([
                'id' => 'batch-xyz',
                'status' => 'completed',
                'request_counts' => ['total' => 2, 'completed' => 2, 'failed' => 0],
                'output_file_id' => 'file-out',
                'error_file_id' => 'file-err',
            ], 200),
            'api.openai.com/v1/files/file-out/content' => Http::response("{\"custom_id\":\"row-0\"}\n", 200),
            'api.openai.com/v1/files/file-err/content' => Http::response("{\"custom_id\":\"row-1\"}\n", 200),
        ]);

        app(BatchStatusPoller::class)->poll($batch);

        $this->assertSame('completed', $batch->status);
        $this->assertNotNull($batch->finished_at);
        $this->assertDatabaseCount('outputs', 2);

        $output = Output::where('batch_id', $batch->id)->where('type', Output::TYPE_RAW_OUTPUT)->firstOrFail();
        Storage::disk('local')->assertExists($output->storage_path);
    }

    public function test_repolling_a_completed_batch_does_not_redownload(): void
    {
        $batch = $this->submittedBatch();

        Http::fake([
            'api.openai.com/v1/batches/batch-xyz' => Http::response([
                'id' => 'batch-xyz',
                'status' => 'completed',
                'output_file_id' => 'file-out',
                'error_file_id' => 'file-err',
            ], 200),
            'api.openai.com/v1/files/*/content' => Http::response("{}\n", 200),
        ]);

        $poller = app(BatchStatusPoller::class);
        $poller->poll($batch);
        $poller->poll($batch);

        $this->assertDatabaseCount('outputs', 2);
    }

    public function test_failed_sets_finished_at_and_error_message(): void
    {
        $batch = $this->submittedBatch();

        Http::fake(['api.openai.com/v1/batches/batch-xyz' => Http::response([
            'id' => 'batch-xyz',
            'status' => 'failed',
            'errors' => ['data' => [['message' => 'Invalid JSONL line 3.']]],
        ], 200)]);

        app(BatchStatusPoller::class)->poll($batch);

        $this->assertSame('failed', $batch->status);
        $this->assertNotNull($batch->finished_at);
        $this->assertSame('Invalid JSONL line 3.', $batch->error_message);
    }

    public function test_is_terminal_matches_completed_failed_expired_and_cancelled_only(): void
    {
        $poller = app(BatchStatusPoller::class);
        $batch = $this->submittedBatch();

        foreach (['queued', 'in_progress', 'finalizing'] as $status) {
            $batch->status = $status;
            $this->assertFalse($poller->isTerminal($batch));
        }

        foreach (['completed', 'failed', 'expired', 'cancelled'] as $status) {
            $batch->status = $status;
            $this->assertTrue($poller->isTerminal($batch));
        }
    }

    public function test_missing_api_key_leaves_the_batch_untouched(): void
    {
        $batch = $this->submittedBatch(withApiKey: false);

        app(BatchStatusPoller::class)->poll($batch);

        $this->assertSame('queued', $batch->status);
        $this->assertNull($batch->last_polled_at);
    }

    public function test_a_transient_api_failure_leaves_the_batch_untouched(): void
    {
        $batch = $this->submittedBatch();

        Http::fake(['api.openai.com/*' => Http::response([], 500)]);

        app(BatchStatusPoller::class)->poll($batch);

        $this->assertSame('queued', $batch->status);
        $this->assertNull($batch->last_polled_at);
    }

    public function test_poll_if_stale_skips_the_real_call_within_the_throttle_window(): void
    {
        $batch = $this->submittedBatch();
        $batch->update(['last_polled_at' => now()->subSeconds(5)]);

        Http::fake(['api.openai.com/*' => Http::response(['id' => 'batch-xyz', 'status' => 'in_progress'], 200)]);

        app(BatchStatusPoller::class)->pollIfStale($batch);

        Http::assertNothingSent();
        $this->assertSame('queued', $batch->status);
    }

    public function test_poll_if_stale_polls_once_the_throttle_window_has_passed(): void
    {
        $batch = $this->submittedBatch();
        $interval = (int) config('services.openai.batch_poll_interval_seconds');
        $batch->update(['last_polled_at' => now()->subSeconds($interval + 5)]);

        Http::fake(['api.openai.com/v1/batches/batch-xyz' => Http::response([
            'id' => 'batch-xyz',
            'status' => 'in_progress',
        ], 200)]);

        app(BatchStatusPoller::class)->pollIfStale($batch);

        Http::assertSentCount(1);
        $this->assertSame('in_progress', $batch->status);
    }

    public function test_poll_if_stale_polls_when_never_polled_before(): void
    {
        $batch = $this->submittedBatch();
        $this->assertNull($batch->last_polled_at);

        Http::fake(['api.openai.com/v1/batches/batch-xyz' => Http::response([
            'id' => 'batch-xyz',
            'status' => 'in_progress',
        ], 200)]);

        app(BatchStatusPoller::class)->pollIfStale($batch);

        Http::assertSentCount(1);
    }

    public function test_poll_if_stale_never_calls_out_for_a_terminal_batch(): void
    {
        $batch = $this->submittedBatch();
        $batch->update(['status' => 'completed', 'last_polled_at' => now()->subDays(1)]);

        Http::fake(['api.openai.com/*' => Http::response(['id' => 'batch-xyz', 'status' => 'completed'], 200)]);

        app(BatchStatusPoller::class)->pollIfStale($batch);

        Http::assertNothingSent();
    }

    public function test_poll_if_stale_never_calls_out_before_the_batch_has_been_submitted(): void
    {
        $batch = $this->submittedBatch();
        $batch->update(['provider_job_id' => null]);

        Http::fake(['api.openai.com/*' => Http::response(['id' => 'batch-xyz', 'status' => 'in_progress'], 200)]);

        app(BatchStatusPoller::class)->pollIfStale($batch);

        Http::assertNothingSent();
    }
}
