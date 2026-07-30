<?php

namespace CatFlow\File\Services\Parsers;

use CatFlow\File\Models\Dataset;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class JsonDatasetParser implements DatasetParserInterface
{
    public function columns(Dataset $dataset): array
    {
        $rows = $this->decode($dataset);

        return $rows === [] ? [] : array_keys($rows[0]);
    }

    public function rows(Dataset $dataset): iterable
    {
        yield from $this->decode($dataset);
    }

    public function countRows(Dataset $dataset): int
    {
        return count($this->decode($dataset));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decode(Dataset $dataset): array
    {
        $contents = Storage::disk('local')->get($dataset->storage_path);

        $decoded = json_decode((string) $contents, true);

        if (! is_array($decoded)) {
            throw new RuntimeException("Dataset file is not a valid JSON array: {$dataset->storage_path}");
        }

        return $decoded;
    }
}
