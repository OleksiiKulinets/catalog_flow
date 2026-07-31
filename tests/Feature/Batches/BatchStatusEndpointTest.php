<?php

namespace Tests\Feature\Batches;

use CatFlow\Batch\Models\Batch;
use CatFlow\File\Models\Dataset;
use CatFlow\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BatchStatusEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function submittedBatch(User $user): Batch
    {
        $dataset = Dataset::create([
            'user_id' => $user->id,
            'name' => 'products.csv',
            'source_type' => Dataset::SOURCE_UPLOAD,
            'source_format' => 'csv',
            'storage_path' => "datasets/{$user->id}/d.csv",
            'rows_count' => 10,
        ]);

        return Batch::create([
            'user_id' => $user->id,
            'dataset_id' => $dataset->id,
            'provider' => 'openai',
            'model' => 'gpt-4.1',
            'output_format' => 'csv',
            'prompt' => 'do something',
            'status' => 'in_progress',
            'provider_job_id' => 'batch-xyz',
            'started_at' => now()->subMinutes(2),
            'request_total' => 10,
            'request_completed' => 4,
        ]);
    }

    public function test_status_endpoint_returns_the_expected_shape(): void
    {
        $user = User::factory()->create();
        $user->apiKey()->create(['provider' => 'openai', 'encrypted_key' => 'sk-test-0123456789', 'last_four' => '6789']);
        $batch = $this->submittedBatch($user);

        Http::fake(['api.openai.com/v1/batches/batch-xyz' => Http::response([
            'id' => 'batch-xyz',
            'status' => 'in_progress',
            'request_counts' => ['total' => 10, 'completed' => 5, 'failed' => 0],
        ], 200)]);

        $response = $this->actingAs($user)->getJson(route('batches.status', $batch));

        $response->assertOk();
        $response->assertJson([
            'status' => 'in_progress',
            'done' => 5,
            'total' => 10,
        ]);
        $response->assertJsonStructure(['status', 'done', 'total', 'eta_seconds', 'eta_human']);
    }

    public function test_status_endpoint_is_scoped_to_the_owning_user(): void
    {
        $owner = User::factory()->create();
        $owner->apiKey()->create(['provider' => 'openai', 'encrypted_key' => 'sk-test-0123456789', 'last_four' => '6789']);
        $batch = $this->submittedBatch($owner);

        $stranger = User::factory()->create();

        $this->actingAs($stranger)->getJson(route('batches.status', $batch))->assertNotFound();
    }

    public function test_status_endpoint_throttles_real_openai_calls(): void
    {
        $user = User::factory()->create();
        $user->apiKey()->create(['provider' => 'openai', 'encrypted_key' => 'sk-test-0123456789', 'last_four' => '6789']);
        $batch = $this->submittedBatch($user);

        Http::fake(['api.openai.com/v1/batches/batch-xyz' => Http::response([
            'id' => 'batch-xyz',
            'status' => 'in_progress',
            'request_counts' => ['total' => 10, 'completed' => 5, 'failed' => 0],
        ], 200)]);

        $this->actingAs($user)->getJson(route('batches.status', $batch))->assertOk();
        $this->actingAs($user)->getJson(route('batches.status', $batch))->assertOk();

        Http::assertSentCount(1);
    }

    /**
     * Regression guard: status must not depend solely on a queue worker
     * being up or the live-polling JS having run — a plain page load has to
     * be able to catch a stale batch up on its own (throttled, same as the
     * JSON endpoint).
     */
    public function test_visiting_the_show_page_also_triggers_a_throttled_status_check(): void
    {
        $user = User::factory()->create();
        $user->apiKey()->create(['provider' => 'openai', 'encrypted_key' => 'sk-test-0123456789', 'last_four' => '6789']);
        $batch = $this->submittedBatch($user);

        Http::fake(['api.openai.com/v1/batches/batch-xyz' => Http::response([
            'id' => 'batch-xyz',
            'status' => 'completed',
            'request_counts' => ['total' => 10, 'completed' => 10, 'failed' => 0],
        ], 200)]);

        $response = $this->actingAs($user)->get(route('batches.show', $batch));

        $response->assertOk();
        Http::assertSentCount(1);

        $batch->refresh();
        $this->assertSame('completed', $batch->status);
    }

    public function test_visiting_the_show_page_does_not_poll_again_within_the_throttle_window(): void
    {
        $user = User::factory()->create();
        $user->apiKey()->create(['provider' => 'openai', 'encrypted_key' => 'sk-test-0123456789', 'last_four' => '6789']);
        $batch = $this->submittedBatch($user);
        $batch->update(['last_polled_at' => now()->subSeconds(5)]);

        Http::fake(['api.openai.com/*' => Http::response(['id' => 'batch-xyz', 'status' => 'in_progress'], 200)]);

        $this->actingAs($user)->get(route('batches.show', $batch))->assertOk();

        Http::assertNothingSent();
    }
}
