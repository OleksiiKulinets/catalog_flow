<?php

namespace Tests\Feature\Batches;

use CatFlow\Analysis\Models\DatasetSchema;
use CatFlow\Batch\Models\Batch;
use CatFlow\File\Models\Dataset;
use CatFlow\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnalyzeBatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function userWithApiKey(): User
    {
        $user = User::factory()->create();

        $user->apiKey()->create([
            'provider' => 'openai',
            'encrypted_key' => 'sk-test-0123456789',
            'last_four' => '6789',
        ]);

        return $user;
    }

    /**
     * @return array{0: Dataset, 1: Batch}
     */
    private function datasetAndBatch(User $user): array
    {
        $path = "datasets/{$user->id}/d.csv";
        Storage::disk('local')->put($path, "name,price\nWidget,9.99\nGadget,19.99\n");

        $dataset = Dataset::create([
            'user_id' => $user->id,
            'name' => 'products.csv',
            'source_type' => Dataset::SOURCE_UPLOAD,
            'source_format' => 'csv',
            'storage_path' => $path,
            'rows_count' => 2,
        ]);

        $batch = Batch::create([
            'user_id' => $user->id,
            'dataset_id' => $dataset->id,
            'provider' => 'openai',
            'model' => 'gpt-4.1',
            'output_format' => 'csv',
            'prompt' => 'Translate the name to Spanish.',
            'status' => 'draft',
        ]);

        return [$dataset, $batch];
    }

    /**
     * @return array<string, mixed>
     */
    private function analysisBody(float $confidence): array
    {
        return [
            'choices' => [
                ['message' => ['content' => json_encode([
                    'columns' => [
                        ['source_column' => 'name', 'canonical_field' => 'name', 'data_type' => 'text', 'confidence' => $confidence, 'example_value' => 'Widget'],
                        ['source_column' => 'price', 'canonical_field' => 'price', 'data_type' => 'currency', 'confidence' => $confidence, 'example_value' => '9.99'],
                    ],
                    'overall_confidence' => $confidence,
                ])]],
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function confirmPayloadFor(DatasetSchema $schema): array
    {
        return $schema->columns->mapWithKeys(fn ($column) => [
            $column->id => [
                'canonical_field' => $column->canonical_field ?? '',
                'data_type' => $column->data_type,
            ],
        ])->all();
    }

    public function test_analyze_renders_a_helpful_message_when_the_user_has_no_api_key(): void
    {
        $user = User::factory()->create();
        [$dataset, $batch] = $this->datasetAndBatch($user);

        $response = $this->actingAs($user)->get(route('batches.analyze', $batch));

        $response->assertOk();
        $response->assertViewIs('batches.analyze');
        $response->assertSee(route('profile.edit'), false);

        $schema = DatasetSchema::where('dataset_id', $dataset->id)->firstOrFail();
        $this->assertSame(DatasetSchema::STATUS_FAILED, $schema->status);
        $this->assertSame('missing_api_key', $schema->error_reason);
    }

    public function test_analyze_runs_ai_and_shows_the_confirmed_preview_when_confidence_is_high(): void
    {
        $user = $this->userWithApiKey();
        [$dataset, $batch] = $this->datasetAndBatch($user);

        Http::fake(['api.openai.com/*' => Http::response($this->analysisBody(0.95), 200)]);

        $response = $this->actingAs($user)->get(route('batches.analyze', $batch));

        $response->assertOk();
        $response->assertViewIs('batches.analyze');
        $response->assertSee('Widget');
        Http::assertSentCount(1);

        $schema = DatasetSchema::where('dataset_id', $dataset->id)->firstOrFail();
        $this->assertSame(DatasetSchema::STATUS_CONFIRMED, $schema->status);
    }

    public function test_analyze_leaves_the_schema_needing_review_when_confidence_stays_low(): void
    {
        $user = $this->userWithApiKey();
        [$dataset, $batch] = $this->datasetAndBatch($user);

        Http::fake([
            'api.openai.com/*' => Http::sequence()
                ->push($this->analysisBody(0.4))
                ->push($this->analysisBody(0.6)),
        ]);

        $response = $this->actingAs($user)->get(route('batches.analyze', $batch));

        $response->assertOk();
        Http::assertSentCount(2);

        $schema = DatasetSchema::where('dataset_id', $dataset->id)->firstOrFail();
        $this->assertSame(DatasetSchema::STATUS_NEEDS_REVIEW, $schema->status);
    }

    public function test_revisiting_analyze_does_not_repeat_the_ai_call(): void
    {
        $user = $this->userWithApiKey();
        [, $batch] = $this->datasetAndBatch($user);

        Http::fake(['api.openai.com/*' => Http::response($this->analysisBody(0.95), 200)]);

        $this->actingAs($user)->get(route('batches.analyze', $batch));
        $this->actingAs($user)->get(route('batches.analyze', $batch))->assertOk();

        Http::assertSentCount(1);
    }

    public function test_confirm_applies_edits_and_builds_the_jsonl_file(): void
    {
        $user = $this->userWithApiKey();
        [$dataset, $batch] = $this->datasetAndBatch($user);

        Http::fake(['api.openai.com/*' => Http::response($this->analysisBody(0.95), 200)]);
        $this->actingAs($user)->get(route('batches.analyze', $batch));

        $schema = DatasetSchema::where('dataset_id', $dataset->id)->firstOrFail();

        $response = $this->actingAs($user)->post(route('batches.confirm', $batch), [
            'columns' => $this->confirmPayloadFor($schema),
        ]);

        $response->assertRedirect(route('batches.show', $batch));

        $batch->refresh();
        $this->assertSame('in_progress', $batch->status);
        $this->assertNotNull($batch->input_jsonl_path);
        Storage::disk('local')->assertExists($batch->input_jsonl_path);

        $schema->refresh();
        $this->assertSame(DatasetSchema::STATUS_CONFIRMED, $schema->status);
        $this->assertTrue($schema->columns->every(fn ($column) => $column->is_confirmed));
    }

    public function test_confirm_can_mark_a_column_as_ignored(): void
    {
        $user = $this->userWithApiKey();
        [$dataset, $batch] = $this->datasetAndBatch($user);

        Http::fake(['api.openai.com/*' => Http::response($this->analysisBody(0.95), 200)]);
        $this->actingAs($user)->get(route('batches.analyze', $batch));

        $schema = DatasetSchema::where('dataset_id', $dataset->id)->firstOrFail();
        $priceColumn = $schema->columns->firstWhere('source_column', 'price');
        $payload = $this->confirmPayloadFor($schema);
        $payload[$priceColumn->id]['canonical_field'] = '';

        $this->actingAs($user)->post(route('batches.confirm', $batch), ['columns' => $payload]);

        $priceColumn->refresh();
        $this->assertNull($priceColumn->canonical_field);
    }

    public function test_confirm_is_rejected_once_the_batch_is_no_longer_a_draft(): void
    {
        $user = $this->userWithApiKey();
        [$dataset, $batch] = $this->datasetAndBatch($user);

        Http::fake(['api.openai.com/*' => Http::response($this->analysisBody(0.95), 200)]);
        $this->actingAs($user)->get(route('batches.analyze', $batch));

        $schema = DatasetSchema::where('dataset_id', $dataset->id)->firstOrFail();
        $payload = ['columns' => $this->confirmPayloadFor($schema)];

        $this->actingAs($user)->post(route('batches.confirm', $batch), $payload);
        $response = $this->actingAs($user)->post(route('batches.confirm', $batch), $payload);

        $response->assertNotFound();
    }

    public function test_analyze_and_confirm_are_scoped_to_the_owning_user(): void
    {
        $owner = $this->userWithApiKey();
        [$dataset, $batch] = $this->datasetAndBatch($owner);

        Http::fake(['api.openai.com/*' => Http::response($this->analysisBody(0.95), 200)]);
        $this->actingAs($owner)->get(route('batches.analyze', $batch));

        $schema = DatasetSchema::where('dataset_id', $dataset->id)->firstOrFail();
        $payload = ['columns' => $this->confirmPayloadFor($schema)];

        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get(route('batches.analyze', $batch))->assertNotFound();
        $this->actingAs($stranger)->post(route('batches.confirm', $batch), $payload)->assertNotFound();
    }

    public function test_analyze_does_not_crash_when_the_dataset_file_is_missing(): void
    {
        $user = $this->userWithApiKey();

        // Never actually written to the fake disk — simulates a missing/corrupt file.
        $dataset = Dataset::create([
            'user_id' => $user->id,
            'name' => 'missing.csv',
            'source_type' => Dataset::SOURCE_UPLOAD,
            'source_format' => 'csv',
            'storage_path' => "datasets/{$user->id}/does-not-exist.csv",
            'rows_count' => 0,
        ]);

        $batch = Batch::create([
            'user_id' => $user->id,
            'dataset_id' => $dataset->id,
            'provider' => 'openai',
            'model' => 'gpt-4.1',
            'output_format' => 'csv',
            'prompt' => 'do something',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->get(route('batches.analyze', $batch));

        $response->assertOk();
        $response->assertViewIs('batches.analyze');

        $schema = DatasetSchema::where('dataset_id', $dataset->id)->firstOrFail();
        $this->assertSame(DatasetSchema::STATUS_FAILED, $schema->status);
    }

    public function test_confirm_does_not_crash_when_columns_is_not_an_array(): void
    {
        $user = $this->userWithApiKey();
        [, $batch] = $this->datasetAndBatch($user);

        Http::fake(['api.openai.com/*' => Http::response($this->analysisBody(0.95), 200)]);
        $this->actingAs($user)->get(route('batches.analyze', $batch));

        $response = $this->actingAs($user)->post(route('batches.confirm', $batch), [
            'columns' => 'not-an-array',
        ]);

        $response->assertSessionHasErrors('columns');
    }
}
