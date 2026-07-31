<?php

namespace CatFlow\Analysis\Services;

use CatFlow\Analysis\Models\DatasetSchema;
use CatFlow\Analysis\OpenAi\AnalysisException;
use CatFlow\Analysis\OpenAi\OpenAiAnalysisClient;
use CatFlow\File\Models\Dataset;

class SchemaAnalyzer
{
    public function __construct(
        private readonly DatasetSampler $sampler,
        private readonly OpenAiAnalysisClient $client,
    ) {
    }

    /**
     * Analyze a dataset's column structure, refining once on a larger
     * sample if the first pass isn't confident enough. One schema per
     * dataset — re-analyzing overwrites the previous attempt.
     */
    public function analyze(Dataset $dataset): DatasetSchema
    {
        $schema = DatasetSchema::updateOrCreate(
            ['dataset_id' => $dataset->id],
            ['status' => DatasetSchema::STATUS_ANALYZING, 'error_message' => null]
        );

        $model = (string) config('services.openai.analysis_model');
        $threshold = (float) config('services.openai.analysis_confidence_threshold');

        try {
            $result = $this->runAttempt($dataset, $schema, $model, (int) config('services.openai.analysis_initial_sample'), 1);

            if ($result['overall_confidence'] < $threshold) {
                $result = $this->runAttempt($dataset, $schema, $model, (int) config('services.openai.analysis_refine_sample'), 2);
            }
        } catch (\Throwable $e) {
            report($e);

            $schema->update([
                'status' => DatasetSchema::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'error_reason' => $e instanceof AnalysisException ? $e->reason : null,
            ]);

            throw $e;
        }

        $confirmed = $result['overall_confidence'] >= $threshold;

        $schema->update([
            'status' => $confirmed ? DatasetSchema::STATUS_CONFIRMED : DatasetSchema::STATUS_NEEDS_REVIEW,
            'confidence' => $result['overall_confidence'],
            'analyzed_at' => now(),
            'confirmed_at' => $confirmed ? now() : null,
        ]);

        if ($confirmed) {
            $schema->columns()->update(['is_confirmed' => true]);
        }

        return $schema->fresh('columns');
    }

    /**
     * @return array{columns: array<int, array<string, mixed>>, overall_confidence: float}
     */
    private function runAttempt(Dataset $dataset, DatasetSchema $schema, string $model, int $sampleSize, int $attemptNumber): array
    {
        $sample = $this->sampler->sample($dataset, $sampleSize);

        $result = $this->client->analyzeColumns($dataset->user, $sample['columns'], $sample['rows'], $model);

        $schema->columns()->delete();

        foreach ($result['columns'] as $index => $column) {
            $schema->columns()->create([
                'source_column' => $column['source_column'],
                'canonical_field' => $column['canonical_field'],
                'data_type' => $column['data_type'],
                'example_value' => $column['example_value'],
                'confidence' => $column['confidence'],
                'sort_order' => $index,
            ]);
        }

        $schema->update([
            'model' => $model,
            'sample_size_used' => $sampleSize,
            'attempts' => $attemptNumber,
            'raw_response' => $result,
        ]);

        return $result;
    }
}
