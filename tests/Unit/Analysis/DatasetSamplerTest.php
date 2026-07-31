<?php

namespace Tests\Unit\Analysis;

use CatFlow\Analysis\Services\DatasetSampler;
use CatFlow\File\Models\Dataset;
use CatFlow\File\Services\Parsers\DatasetParserFactory;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatasetSamplerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_sample_limits_rows_and_still_returns_all_columns(): void
    {
        $rows = implode("\n", array_map(fn ($i) => "Item{$i},{$i}.00", range(1, 30)));
        Storage::disk('local')->put('d.csv', "name,price\n{$rows}\n");
        $dataset = new Dataset(['source_format' => 'csv', 'storage_path' => 'd.csv']);

        $sample = (new DatasetSampler(new DatasetParserFactory()))->sample($dataset, 10);

        $this->assertSame(['name', 'price'], $sample['columns']);
        $this->assertCount(10, $sample['rows']);
        $this->assertSame('Item1', $sample['rows'][0]['name']);
    }

    public function test_sample_returns_all_rows_when_dataset_is_smaller_than_the_limit(): void
    {
        Storage::disk('local')->put('small.csv', "name,price\nWidget,9.99\n");
        $dataset = new Dataset(['source_format' => 'csv', 'storage_path' => 'small.csv']);

        $sample = (new DatasetSampler(new DatasetParserFactory()))->sample($dataset, 20);

        $this->assertCount(1, $sample['rows']);
    }
}
