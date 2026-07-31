<?php

namespace Tests\Unit\Analysis;

use CatFlow\Analysis\Jsonl\BatchJsonlBuilder;
use CatFlow\Analysis\Jsonl\ProductRowMapper;
use CatFlow\Analysis\Jsonl\PromptTemplateRenderer;
use CatFlow\Analysis\Models\DatasetSchema;
use CatFlow\Batch\Models\Batch;
use CatFlow\File\Models\Dataset;
use CatFlow\File\Services\Parsers\DatasetParserFactory;
use CatFlow\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class BatchJsonlBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function builder(): BatchJsonlBuilder
    {
        return new BatchJsonlBuilder(new DatasetParserFactory(), new ProductRowMapper(), new PromptTemplateRenderer());
    }

    private function makeDataset(User $user, string $csv): Dataset
    {
        $path = "datasets/{$user->id}/d.csv";
        Storage::disk('local')->put($path, $csv);

        return Dataset::create([
            'user_id' => $user->id,
            'name' => 'products.csv',
            'source_type' => Dataset::SOURCE_UPLOAD,
            'source_format' => 'csv',
            'storage_path' => $path,
            'rows_count' => substr_count($csv, "\n") - 1,
        ]);
    }

    private function makeBatch(User $user, Dataset $dataset, string $prompt = 'Translate the product name to Spanish.'): Batch
    {
        return Batch::create([
            'user_id' => $user->id,
            'dataset_id' => $dataset->id,
            'provider' => 'openai',
            'model' => 'gpt-4.1',
            'output_format' => 'csv',
            'prompt' => $prompt,
            'status' => 'draft',
        ]);
    }

    public function test_builds_one_jsonl_line_per_row_using_the_confirmed_schema(): void
    {
        $user = User::factory()->create();
        $dataset = $this->makeDataset($user, "name,price\nWidget,9.99\nGadget,19.99\n");

        $schema = DatasetSchema::create(['dataset_id' => $dataset->id, 'status' => DatasetSchema::STATUS_CONFIRMED]);
        $schema->columns()->create(['source_column' => 'name', 'canonical_field' => 'name', 'data_type' => 'text', 'is_confirmed' => true, 'sort_order' => 0]);
        $schema->columns()->create(['source_column' => 'price', 'canonical_field' => 'price', 'data_type' => 'currency', 'is_confirmed' => true, 'sort_order' => 1]);

        $batch = $this->makeBatch($user, $dataset);

        $path = $this->builder()->build($batch);

        Storage::disk('local')->assertExists($path);

        $lines = array_values(array_filter(explode("\n", Storage::disk('local')->get($path))));
        $this->assertCount(2, $lines);

        $first = json_decode($lines[0], true);
        $this->assertSame('batch-'.$batch->id.'-row-0', $first['custom_id']);
        $this->assertSame('POST', $first['method']);
        $this->assertSame('/v1/chat/completions', $first['url']);
        $this->assertSame('gpt-4.1', $first['body']['model']);
        $this->assertStringContainsString('Translate the product name to Spanish.', $first['body']['messages'][0]['content']);
        $this->assertStringContainsString('name: Widget', $first['body']['messages'][0]['content']);
        $this->assertStringContainsString('price: 9.99', $first['body']['messages'][0]['content']);

        $second = json_decode($lines[1], true);
        $this->assertSame('batch-'.$batch->id.'-row-1', $second['custom_id']);
    }

    public function test_columns_not_mapped_to_a_canonical_field_are_left_out_of_the_product_data(): void
    {
        $user = User::factory()->create();
        $dataset = $this->makeDataset($user, "name,internal_id\nWidget,999\n");

        $schema = DatasetSchema::create(['dataset_id' => $dataset->id, 'status' => DatasetSchema::STATUS_CONFIRMED]);
        $schema->columns()->create(['source_column' => 'name', 'canonical_field' => 'name', 'data_type' => 'text', 'is_confirmed' => true, 'sort_order' => 0]);
        $schema->columns()->create(['source_column' => 'internal_id', 'canonical_field' => null, 'data_type' => 'text', 'is_confirmed' => true, 'sort_order' => 1]);

        $batch = $this->makeBatch($user, $dataset);

        $path = $this->builder()->build($batch);

        $line = json_decode(trim(Storage::disk('local')->get($path)), true);
        $this->assertStringNotContainsString('internal_id', $line['body']['messages'][0]['content']);
        $this->assertStringNotContainsString('999', $line['body']['messages'][0]['content']);
    }

    public function test_throws_when_the_dataset_has_no_confirmed_schema(): void
    {
        $user = User::factory()->create();
        $dataset = $this->makeDataset($user, "name\nWidget\n");
        $batch = $this->makeBatch($user, $dataset);

        $this->expectException(RuntimeException::class);

        $this->builder()->build($batch);
    }

    public function test_throws_when_the_schema_still_needs_review(): void
    {
        $user = User::factory()->create();
        $dataset = $this->makeDataset($user, "name\nWidget\n");
        DatasetSchema::create(['dataset_id' => $dataset->id, 'status' => DatasetSchema::STATUS_NEEDS_REVIEW]);
        $batch = $this->makeBatch($user, $dataset);

        $this->expectException(RuntimeException::class);

        $this->builder()->build($batch);
    }
}
