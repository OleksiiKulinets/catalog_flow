<?php

namespace CatFlow\Analysis\Services;

use CatFlow\File\Models\Dataset;
use CatFlow\File\Services\Parsers\DatasetParserFactory;

class DatasetSampler
{
    public function __construct(
        private readonly DatasetParserFactory $parsers,
    ) {
    }

    /**
     * Column headers plus the first $limit data rows of the dataset, used as
     * the AI's view of the file when detecting column structure.
     *
     * @return array{columns: array<int, string>, rows: array<int, array<string, mixed>>}
     */
    public function sample(Dataset $dataset, int $limit): array
    {
        $parser = $this->parsers->make($dataset);

        $rows = [];

        foreach ($parser->rows($dataset) as $row) {
            if (count($rows) >= $limit) {
                break;
            }

            $rows[] = $row;
        }

        return [
            'columns' => $parser->columns($dataset),
            'rows' => $rows,
        ];
    }
}
