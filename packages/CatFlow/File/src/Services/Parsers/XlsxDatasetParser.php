<?php

namespace CatFlow\File\Services\Parsers;

use CatFlow\File\Models\Dataset;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class XlsxDatasetParser implements DatasetParserInterface
{
    /**
     * Memoizes the parsed sheet for the lifetime of this instance.
     * columns()/rows()/countRows() each used to re-run the full
     * IOFactory::load() + toArray() from scratch — for XLSX that's a real
     * cost (PhpSpreadsheet builds an in-memory object graph for the whole
     * workbook), and callers like DatasetSampler::sample() call both
     * columns() and rows() on the same instance for the same file, so it
     * was parsing the same file twice for no reason.
     *
     * @var array<int, array<int, mixed>>|null
     */
    private ?array $cachedRows = null;

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
        if ($this->cachedRows !== null) {
            return $this->cachedRows;
        }

        $path = Storage::disk('local')->path($dataset->storage_path);

        // Data-only: skips building style/formatting objects for every cell,
        // which we never read — meaningful memory/time savings on sheets
        // with heavy formatting, and no behavior change since toArray()
        // below only ever looked at values anyway.
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        $rows = $reader->load($path)->getActiveSheet()->toArray();

        // toArray() reads through the sheet's full used dimension, which
        // PhpSpreadsheet expands to cover any cell that ever had styling
        // applied (fill, border, ...) even with no value in it — e.g. a
        // template or a "select-all-and-fill-color" habit stretched to row
        // 999. That reports a wildly inflated row count and, further down
        // the pipeline, would feed hundreds of blank rows into the AI
        // analysis and the OpenAI batch itself. Drop fully-blank rows so
        // this reflects actual data, not the sheet's stylistic footprint.
        return $this->cachedRows = array_values(array_filter(
            $rows,
            fn (array $row) => array_filter($row, fn ($value) => $value !== null && $value !== '') !== []
        ));
    }
}
