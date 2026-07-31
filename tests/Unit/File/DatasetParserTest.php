<?php

namespace Tests\Unit\File;

use CatFlow\File\Models\Dataset;
use CatFlow\File\Services\Parsers\CsvDatasetParser;
use CatFlow\File\Services\Parsers\DatasetParserFactory;
use CatFlow\File\Services\Parsers\JsonDatasetParser;
use CatFlow\File\Services\Parsers\XlsxDatasetParser;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class DatasetParserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function datasetWithContent(string $format, string $path, string $contents): Dataset
    {
        Storage::disk('local')->put($path, $contents);

        return new Dataset([
            'source_format' => $format,
            'storage_path' => $path,
        ]);
    }

    public function test_csv_parser_reads_columns_and_rows(): void
    {
        $dataset = $this->datasetWithContent('csv', 'd.csv', "name,price\nWidget,9.99\nGadget,19.99\n");
        $parser = new CsvDatasetParser();

        $this->assertSame(['name', 'price'], $parser->columns($dataset));
        $this->assertSame(2, $parser->countRows($dataset));
        $this->assertSame(
            [['name' => 'Widget', 'price' => '9.99'], ['name' => 'Gadget', 'price' => '19.99']],
            iterator_to_array($parser->rows($dataset))
        );
    }

    public function test_csv_parser_handles_ragged_rows(): void
    {
        // A short row (missing trailing field) and a long row (stray comma)
        // — array_combine() would fatal on either without padding/truncating.
        $dataset = $this->datasetWithContent(
            'csv',
            'ragged.csv',
            "name,price,stock\nWidget,9.99\nGadget,19.99,5,extra\n"
        );
        $parser = new CsvDatasetParser();

        $rows = iterator_to_array($parser->rows($dataset));

        $this->assertSame(['name' => 'Widget', 'price' => '9.99', 'stock' => null], $rows[0]);
        $this->assertSame(['name' => 'Gadget', 'price' => '19.99', 'stock' => '5'], $rows[1]);
    }

    public function test_json_parser_reads_columns_and_rows(): void
    {
        $contents = json_encode([
            ['name' => 'Widget', 'price' => 9.99],
            ['name' => 'Gadget', 'price' => 19.99],
        ]);
        $dataset = $this->datasetWithContent('json', 'd.json', $contents);
        $parser = new JsonDatasetParser();

        $this->assertSame(['name', 'price'], $parser->columns($dataset));
        $this->assertSame(2, $parser->countRows($dataset));
    }

    public function test_xlsx_parser_reads_columns_and_rows(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['name', 'price'],
            ['Widget', 9.99],
            ['Gadget', 19.99],
        ], null, 'A1');

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($spreadsheet))->save($tmpFile);
        $contents = file_get_contents($tmpFile);
        unlink($tmpFile);

        $dataset = $this->datasetWithContent('xlsx', 'd.xlsx', $contents);
        $parser = new XlsxDatasetParser();

        $this->assertSame(['name', 'price'], $parser->columns($dataset));
        $this->assertSame(2, $parser->countRows($dataset));
    }

    /**
     * Regression guard: PhpSpreadsheet's toArray() reads through the
     * sheet's full "used" dimension, which expands to cover any cell that
     * ever had styling applied — even with no value — e.g. a template or a
     * select-all-and-fill-color habit stretched far past the real data.
     * That must not inflate the row count or leak hundreds of blank rows
     * into downstream AI analysis / batch generation.
     */
    public function test_xlsx_parser_ignores_rows_that_only_have_styling_and_no_values(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['name', 'price'],
            ['Widget', 9.99],
            ['Gadget', 19.99],
        ], null, 'A1');

        // Style a much larger range than the real data, with no values in it.
        $sheet->getStyle('A1:B999')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($spreadsheet))->save($tmpFile);
        $contents = file_get_contents($tmpFile);
        unlink($tmpFile);

        $dataset = $this->datasetWithContent('xlsx', 'styled.xlsx', $contents);
        $parser = new XlsxDatasetParser();

        $this->assertSame(2, $parser->countRows($dataset));
        $this->assertCount(2, iterator_to_array($parser->rows($dataset)));
    }

    /**
     * Regression guard: columns()/rows()/countRows() used to each re-parse
     * the whole file from scratch even when called back-to-back on the same
     * instance (exactly what DatasetSampler::sample() does). Proven here by
     * changing the file on disk after the first call — a second call on the
     * same instance must still see the original (cached) data.
     */
    public function test_xlsx_parser_caches_the_parsed_sheet_per_instance(): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([['name', 'price'], ['Widget', 9.99]], null, 'A1');
        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($spreadsheet))->save($tmpFile);
        $dataset = $this->datasetWithContent('xlsx', 'cache.xlsx', file_get_contents($tmpFile));
        unlink($tmpFile);

        $parser = new XlsxDatasetParser();
        $this->assertSame(1, $parser->countRows($dataset));

        // Overwrite the underlying file with different content on the same path.
        Storage::disk('local')->put('cache.xlsx', 'not a real xlsx file anymore');

        $this->assertSame(1, $parser->countRows($dataset));
        $this->assertSame(['name', 'price'], $parser->columns($dataset));
    }

    public function test_json_parser_caches_the_decoded_file_per_instance(): void
    {
        $dataset = $this->datasetWithContent('json', 'cache.json', json_encode([['name' => 'Widget']]));

        $parser = new JsonDatasetParser();
        $this->assertSame(1, $parser->countRows($dataset));

        Storage::disk('local')->put('cache.json', 'not valid json anymore');

        $this->assertSame(1, $parser->countRows($dataset));
    }

    public function test_factory_resolves_parser_by_source_format(): void
    {
        $factory = new DatasetParserFactory();

        $this->assertInstanceOf(CsvDatasetParser::class, $factory->make(new Dataset(['source_format' => 'csv'])));
        $this->assertInstanceOf(XlsxDatasetParser::class, $factory->make(new Dataset(['source_format' => 'xlsx'])));
        $this->assertInstanceOf(JsonDatasetParser::class, $factory->make(new Dataset(['source_format' => 'json'])));
    }

    public function test_factory_throws_for_unsupported_format(): void
    {
        $this->expectException(\RuntimeException::class);

        (new DatasetParserFactory())->make(new Dataset(['source_format' => 'pdf']));
    }
}
