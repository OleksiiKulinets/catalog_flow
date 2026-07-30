<?php

namespace CatFlow\File\Services\Parsers;

use CatFlow\File\Models\Dataset;

interface DatasetParserInterface
{
    /**
     * Column names, in file order.
     *
     * @return array<int, string>
     */
    public function columns(Dataset $dataset): array;

    /**
     * Data rows, each keyed by column name.
     *
     * @return iterable<int, array<string, mixed>>
     */
    public function rows(Dataset $dataset): iterable;

    public function countRows(Dataset $dataset): int;
}
