<?php

namespace CatFlow\Analysis\Jsonl;

use CatFlow\Analysis\Models\DatasetSchema;
use CatFlow\Batch\Models\Batch;
use CatFlow\File\Services\Parsers\DatasetParserFactory;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Streams every row of a batch's dataset through the confirmed schema and
 * the user's prompt, writing one OpenAI Batch API request per line. No AI
 * calls here — only SchemaAnalyzer (once, on a sample) talks to OpenAI; this
 * class just applies the already-confirmed mapping deterministically to
 * however many rows the dataset has.
 */
class BatchJsonlBuilder
{
    public function __construct(
        private readonly DatasetParserFactory $parsers,
        private readonly ProductRowMapper $mapper,
        private readonly PromptTemplateRenderer $renderer,
    ) {
    }

    /**
     * Build the .jsonl file for a batch and return its storage path.
     */
    public function build(Batch $batch): string
    {
        $dataset = $batch->dataset;

        $schema = DatasetSchema::where('dataset_id', $dataset->id)->first();

        if (! $schema || ! $schema->isConfirmed()) {
            throw new RuntimeException("Dataset {$dataset->id} has no confirmed schema yet.");
        }

        $columns = $schema->columns;
        $path = "datasets/{$dataset->user_id}/batches/{$batch->id}.jsonl";

        Storage::disk('local')->makeDirectory(dirname($path));

        $handle = fopen(Storage::disk('local')->path($path), 'w');

        if ($handle === false) {
            throw new RuntimeException("Unable to open jsonl file for writing: {$path}");
        }

        $index = 0;

        foreach ($this->parsers->make($dataset)->rows($dataset) as $row) {
            $product = $this->mapper->map($row, $columns);
            $content = $this->renderer->render($batch->prompt, $product);

            fwrite($handle, json_encode([
                'custom_id' => "batch-{$batch->id}-row-{$index}",
                'method' => 'POST',
                'url' => '/v1/chat/completions',
                'body' => [
                    'model' => $batch->model,
                    'messages' => [
                        ['role' => 'user', 'content' => $content],
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE)."\n");

            $index++;
        }

        fclose($handle);

        return $path;
    }
}
