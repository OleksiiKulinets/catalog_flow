<?php

namespace Tests\Unit\Analysis;

use CatFlow\Analysis\Models\DatasetSchema;
use CatFlow\Analysis\OpenAi\AnalysisException;
use CatFlow\Analysis\Services\SchemaAnalyzer;
use CatFlow\File\Models\Dataset;
use CatFlow\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SchemaAnalyzerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function makeDataset(User $user): Dataset
    {
        $path = "datasets/{$user->id}/d.csv";
        Storage::disk('local')->put($path, "name,price\nWidget,9.99\nGadget,19.99\n");

        return Dataset::create([
            'user_id' => $user->id,
            'name' => 'products.csv',
            'source_type' => Dataset::SOURCE_UPLOAD,
            'source_format' => 'csv',
            'original_filename' => 'products.csv',
            'storage_path' => $path,
            'rows_count' => 2,
        ]);
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

    public function test_high_confidence_on_the_first_pass_confirms_immediately(): void
    {
        $user = $this->userWithApiKey();
        $dataset = $this->makeDataset($user);

        Http::fake(['api.openai.com/*' => Http::response($this->analysisBody(0.95), 200)]);

        $schema = app(SchemaAnalyzer::class)->analyze($dataset);

        $this->assertSame(DatasetSchema::STATUS_CONFIRMED, $schema->status);
        $this->assertSame(1, $schema->attempts);
        $this->assertNotNull($schema->confirmed_at);
        $this->assertCount(2, $schema->columns);
        $this->assertTrue($schema->columns->every(fn ($column) => $column->is_confirmed));
        Http::assertSentCount(1);
    }

    public function test_low_confidence_on_the_first_pass_triggers_a_refine_attempt(): void
    {
        $user = $this->userWithApiKey();
        $dataset = $this->makeDataset($user);

        Http::fake([
            'api.openai.com/*' => Http::sequence()
                ->push($this->analysisBody(0.5))
                ->push($this->analysisBody(0.95)),
        ]);

        $schema = app(SchemaAnalyzer::class)->analyze($dataset);

        $this->assertSame(DatasetSchema::STATUS_CONFIRMED, $schema->status);
        $this->assertSame(2, $schema->attempts);
        Http::assertSentCount(2);
    }

    public function test_two_low_confidence_attempts_land_in_needs_review(): void
    {
        $user = $this->userWithApiKey();
        $dataset = $this->makeDataset($user);

        Http::fake([
            'api.openai.com/*' => Http::sequence()
                ->push($this->analysisBody(0.4))
                ->push($this->analysisBody(0.6)),
        ]);

        $schema = app(SchemaAnalyzer::class)->analyze($dataset);

        $this->assertSame(DatasetSchema::STATUS_NEEDS_REVIEW, $schema->status);
        $this->assertSame(2, $schema->attempts);
        $this->assertNull($schema->confirmed_at);
        $this->assertFalse($schema->columns->contains(fn ($column) => $column->is_confirmed));
        Http::assertSentCount(2);
    }

    public function test_an_ai_failure_marks_the_schema_failed_and_rethrows(): void
    {
        $user = $this->userWithApiKey();
        $dataset = $this->makeDataset($user);

        Http::fake(['api.openai.com/*' => Http::response([], 401)]);

        try {
            app(SchemaAnalyzer::class)->analyze($dataset);
            $this->fail('Expected AnalysisException to be thrown.');
        } catch (AnalysisException) {
            // expected
        }

        $schema = DatasetSchema::where('dataset_id', $dataset->id)->first();
        $this->assertSame(DatasetSchema::STATUS_FAILED, $schema->status);
        $this->assertNotNull($schema->error_message);
        $this->assertSame('invalid_api_key', $schema->error_reason);
    }

    public function test_a_non_analysis_exception_during_sampling_also_marks_the_schema_failed_and_rethrows(): void
    {
        $user = $this->userWithApiKey();

        // Points at a file that was never actually stored, so the parser
        // throws a plain RuntimeException (not an AnalysisException) when
        // it tries to open it — this must be handled just as gracefully as
        // an OpenAI-side failure.
        $dataset = Dataset::create([
            'user_id' => $user->id,
            'name' => 'missing.csv',
            'source_type' => Dataset::SOURCE_UPLOAD,
            'source_format' => 'csv',
            'storage_path' => "datasets/{$user->id}/does-not-exist.csv",
            'rows_count' => 0,
        ]);

        try {
            app(SchemaAnalyzer::class)->analyze($dataset);
            $this->fail('Expected an exception to be thrown.');
        } catch (AnalysisException $e) {
            $this->fail('Did not expect an AnalysisException, got: '.$e->getMessage());
        } catch (\Throwable) {
            // expected — some non-AnalysisException failure (e.g. the file
            // couldn't be opened), which must still be handled gracefully.
        }

        $schema = DatasetSchema::where('dataset_id', $dataset->id)->first();
        $this->assertSame(DatasetSchema::STATUS_FAILED, $schema->status);
        $this->assertNotNull($schema->error_message);
        $this->assertNull($schema->error_reason);
    }
}
