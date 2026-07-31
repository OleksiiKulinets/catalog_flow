<?php

namespace Tests\Unit\Batch;

use CatFlow\Batch\Models\Batch;
use CatFlow\Batch\OpenAi\BatchApiException;
use CatFlow\Batch\OpenAi\OpenAiBatchClient;
use CatFlow\Batch\Services\BatchSubmissionService;
use CatFlow\File\Models\Dataset;
use CatFlow\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BatchSubmissionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function batchWithJsonl(bool $withApiKey = true): Batch
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
            'rows_count' => 1,
        ]);

        $jsonlPath = "datasets/{$user->id}/batches/1.jsonl";
        Storage::disk('local')->put($jsonlPath, "{\"custom_id\":\"batch-1-row-0\"}\n");

        return Batch::create([
            'user_id' => $user->id,
            'dataset_id' => $dataset->id,
            'provider' => 'openai',
            'model' => 'gpt-4.1',
            'output_format' => 'csv',
            'prompt' => 'do something',
            'status' => 'draft',
            'input_jsonl_path' => $jsonlPath,
        ]);
    }

    public function test_submit_persists_the_openai_file_and_batch_ids(): void
    {
        $batch = $this->batchWithJsonl();

        Http::fake([
            'api.openai.com/v1/files' => Http::response(['id' => 'file-abc'], 200),
            'api.openai.com/v1/batches' => Http::response(['id' => 'batch-xyz', 'status' => 'validating'], 200),
        ]);

        app(BatchSubmissionService::class)->submit($batch);

        $batch->refresh();
        $this->assertSame('file-abc', $batch->input_file_id);
        $this->assertSame('batch-xyz', $batch->provider_job_id);
        $this->assertSame('queued', $batch->status);
        $this->assertNotNull($batch->started_at);
    }

    public function test_submit_throws_when_the_user_has_no_api_key(): void
    {
        $batch = $this->batchWithJsonl(withApiKey: false);

        $this->expectException(BatchApiException::class);

        app(BatchSubmissionService::class)->submit($batch);
    }

    public function test_submit_propagates_client_failures_without_marking_the_batch_failed_itself(): void
    {
        $batch = $this->batchWithJsonl();

        Http::fake(['api.openai.com/*' => Http::response([], 401)]);

        try {
            app(BatchSubmissionService::class)->submit($batch);
            $this->fail('Expected BatchApiException to be thrown.');
        } catch (BatchApiException) {
            // expected — the caller (BatchController::confirm()) decides how to
            // record the failure, same convention as OpenAiAnalysisClient.
        }

        $batch->refresh();
        $this->assertSame('uploading', $batch->status);
    }
}
