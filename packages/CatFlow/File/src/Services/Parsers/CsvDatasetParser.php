<?php

namespace CatFlow\File\Services\Parsers;

use CatFlow\File\Models\Dataset;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CsvDatasetParser implements DatasetParserInterface
{
    public function columns(Dataset $dataset): array
    {
        $handle = $this->open($dataset);

        $header = fgetcsv($handle) ?: [];

        fclose($handle);

        return $header;
    }

    public function rows(Dataset $dataset): iterable
    {
        $handle = $this->open($dataset);

        $header = fgetcsv($handle) ?: [];
        $columns = count($header);

        while (($row = fgetcsv($handle)) !== false) {
            // Real-world CSVs are often ragged (a trailing empty field
            // dropped, an extra stray comma) — array_combine() fatals if the
            // row's column count doesn't exactly match the header's.
            yield array_combine($header, array_pad(array_slice($row, 0, $columns), $columns, null));
        }

        fclose($handle);
    }

    public function countRows(Dataset $dataset): int
    {
        return iterator_count($this->rows($dataset));
    }

    /**
     * @return resource
     */
    private function open(Dataset $dataset)
    {
        $path = Storage::disk('local')->path($dataset->storage_path);

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException("Unable to open dataset file: {$dataset->storage_path}");
        }

        return $handle;
    }
}
