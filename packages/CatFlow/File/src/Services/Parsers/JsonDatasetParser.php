<?php

namespace CatFlow\File\Services\Parsers;

use CatFlow\File\Models\Dataset;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class JsonDatasetParser implements DatasetParserInterface
{
    /**
     * Memoizes the decoded file for the lifetime of this instance — see the
     * same caching note on XlsxDatasetParser::$cachedRows; columns()/rows()/
     * countRows() otherwise each re-read and re-decode the whole file from
     * scratch even when called back-to-back on the same instance.
     *
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $cachedRows = null;

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
        if ($this->cachedRows !== null) {
            return $this->cachedRows;
        }

        $contents = Storage::disk('local')->get($dataset->storage_path);

        $decoded = json_decode((string) $contents, true);

        if (! is_array($decoded)) {
            throw new RuntimeException("Dataset file is not a valid JSON array: {$dataset->storage_path}");
        }

        return $this->cachedRows = $decoded;
    }
}
