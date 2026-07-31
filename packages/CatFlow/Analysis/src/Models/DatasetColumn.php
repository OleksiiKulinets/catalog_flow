<?php

namespace CatFlow\Analysis\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatasetColumn extends Model
{
    public const FIELD_NAME = 'name';

    public const FIELD_DESCRIPTION = 'description';

    public const FIELD_SKU = 'sku';

    public const FIELD_PRICE = 'price';

    public const FIELD_CURRENCY = 'currency';

    public const FIELD_CATEGORY = 'category';

    public const FIELD_BRAND = 'brand';

    public const FIELD_QUANTITY = 'quantity';

    public const FIELD_UNIT = 'unit';

    public const FIELD_IMAGE_URL = 'image_url';

    public const FIELD_COLOR = 'color';

    public const FIELD_SIZE = 'size';

    public const FIELD_WEIGHT = 'weight';

    public const FIELD_BARCODE = 'barcode';

    public const FIELD_URL = 'url';

    public const TYPE_TEXT = 'text';

    public const TYPE_NUMBER = 'number';

    public const TYPE_CURRENCY = 'currency';

    public const TYPE_URL = 'url';

    public const TYPE_DATE = 'date';

    public const TYPE_BOOLEAN = 'boolean';

    protected $fillable = [
        'dataset_schema_id',
        'source_column',
        'canonical_field',
        'data_type',
        'currency_code',
        'example_value',
        'confidence',
        'is_confirmed',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'float',
            'is_confirmed' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function schema(): BelongsTo
    {
        return $this->belongsTo(DatasetSchema::class, 'dataset_schema_id');
    }

    /**
     * The fixed vocabulary of product fields the AI is asked to map dataset
     * columns onto. Kept here (rather than in a config file) since it also
     * drives validation and the AI prompt's response schema, not just a
     * runtime setting.
     *
     * @return array<int, string>
     */
    public static function allFields(): array
    {
        return [
            self::FIELD_NAME,
            self::FIELD_DESCRIPTION,
            self::FIELD_SKU,
            self::FIELD_PRICE,
            self::FIELD_CURRENCY,
            self::FIELD_CATEGORY,
            self::FIELD_BRAND,
            self::FIELD_QUANTITY,
            self::FIELD_UNIT,
            self::FIELD_IMAGE_URL,
            self::FIELD_COLOR,
            self::FIELD_SIZE,
            self::FIELD_WEIGHT,
            self::FIELD_BARCODE,
            self::FIELD_URL,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function allDataTypes(): array
    {
        return [
            self::TYPE_TEXT,
            self::TYPE_NUMBER,
            self::TYPE_CURRENCY,
            self::TYPE_URL,
            self::TYPE_DATE,
            self::TYPE_BOOLEAN,
        ];
    }
}
