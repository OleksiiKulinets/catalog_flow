<?php

namespace Tests\Unit\Analysis;

use CatFlow\Analysis\Jsonl\ProductRowMapper;
use CatFlow\Analysis\Models\DatasetColumn;
use Tests\TestCase;

class ProductRowMapperTest extends TestCase
{
    private function column(string $source, ?string $field, string $type): DatasetColumn
    {
        return new DatasetColumn([
            'source_column' => $source,
            'canonical_field' => $field,
            'data_type' => $type,
        ]);
    }

    public function test_maps_currency_with_comma_decimal_and_space_thousands_separator(): void
    {
        $mapper = new ProductRowMapper();

        $product = $mapper->map(
            ['price' => '1 200,00 грн'],
            [$this->column('price', DatasetColumn::FIELD_PRICE, DatasetColumn::TYPE_CURRENCY)]
        );

        $this->assertSame(1200.0, $product['price']);
        $this->assertSame('UAH', $product['currency']);
    }

    public function test_maps_plain_integer(): void
    {
        $mapper = new ProductRowMapper();

        $product = $mapper->map(
            ['qty' => '42'],
            [$this->column('qty', DatasetColumn::FIELD_QUANTITY, DatasetColumn::TYPE_NUMBER)]
        );

        $this->assertSame(42, $product['quantity']);
    }

    public function test_maps_decimal_number(): void
    {
        $mapper = new ProductRowMapper();

        $product = $mapper->map(
            ['w' => '3.14'],
            [$this->column('w', DatasetColumn::FIELD_WEIGHT, DatasetColumn::TYPE_NUMBER)]
        );

        $this->assertSame(3.14, $product['weight']);
    }

    public function test_maps_a_valid_date_to_a_normalized_string(): void
    {
        $mapper = new ProductRowMapper();

        $product = $mapper->map(
            ['d' => '15.01.2024'],
            [$this->column('d', DatasetColumn::FIELD_NAME, DatasetColumn::TYPE_DATE)]
        );

        $this->assertSame('2024-01-15', $product['name']);
    }

    public function test_an_unparseable_date_becomes_null(): void
    {
        $mapper = new ProductRowMapper();

        $product = $mapper->map(
            ['d' => 'not-a-date'],
            [$this->column('d', DatasetColumn::FIELD_NAME, DatasetColumn::TYPE_DATE)]
        );

        $this->assertNull($product['name']);
    }

    public function test_maps_common_boolean_tokens(): void
    {
        $mapper = new ProductRowMapper();
        $column = $this->column('flag', DatasetColumn::FIELD_QUANTITY, DatasetColumn::TYPE_BOOLEAN);

        $this->assertTrue($mapper->map(['flag' => 'так'], [$column])['quantity']);
        $this->assertTrue($mapper->map(['flag' => 'yes'], [$column])['quantity']);
        $this->assertFalse($mapper->map(['flag' => 'ні'], [$column])['quantity']);
        $this->assertFalse($mapper->map(['flag' => 'no'], [$column])['quantity']);
        $this->assertNull($mapper->map(['flag' => 'maybe'], [$column])['quantity']);
    }

    public function test_skips_columns_that_are_not_mapped_to_a_canonical_field(): void
    {
        $mapper = new ProductRowMapper();

        $product = $mapper->map(
            ['internal_id' => '999', 'name' => 'Widget'],
            [
                $this->column('internal_id', null, DatasetColumn::TYPE_TEXT),
                $this->column('name', DatasetColumn::FIELD_NAME, DatasetColumn::TYPE_TEXT),
            ]
        );

        $this->assertArrayNotHasKey('internal_id', $product);
        $this->assertSame('Widget', $product['name']);
    }
}
