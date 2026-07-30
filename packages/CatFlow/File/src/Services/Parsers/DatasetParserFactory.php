<?php

namespace CatFlow\File\Services\Parsers;

use CatFlow\File\Models\Dataset;
use RuntimeException;

class DatasetParserFactory
{
    public function make(Dataset $dataset): DatasetParserInterface
    {
        return match ($dataset->source_format) {
            'csv' => new CsvDatasetParser(),
            'xlsx', 'xls' => new XlsxDatasetParser(),
            'json' => new JsonDatasetParser(),
            default => throw new RuntimeException("Unsupported dataset format: {$dataset->source_format}"),
        };
    }
}
