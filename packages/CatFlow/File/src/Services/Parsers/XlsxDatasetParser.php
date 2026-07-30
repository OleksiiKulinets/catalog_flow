<?php

namespace CatFlow\File\Services\Parsers;

use CatFlow\File\Models\Dataset;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class XlsxDatasetParser implements DatasetParserInterface
{
    public function columns(Dataset $dataset): array
    {
        return $this->sheetRows($dataset)[0] ?? [];
    }

    public function rows(Dataset $dataset): iterable
    {
        $rows = $this->sheetRows($dataset);
        $header = array_shift($rows) ?? [];
        $columns = count($header);

        foreach ($rows as $row) {
            // A sparse/merged-cell sheet can yield a row shorter or longer
            // than the header — array_combine() fatals on a length mismatch.
            yield array_combine($header, array_pad(array_slice($row, 0, $columns), $columns, null));
        }
    }

    public function countRows(Dataset $dataset): int
    {
        return max(count($this->sheetRows($dataset)) - 1, 0);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function sheetRows(Dataset $dataset): array
    {
        $path = Storage::disk('local')->path($dataset->storage_path);

        return IOFactory::load($path)->getActiveSheet()->toArray();
    }
}
