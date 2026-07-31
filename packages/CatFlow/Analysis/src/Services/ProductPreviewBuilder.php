<?php

namespace CatFlow\Analysis\Services;

use CatFlow\Analysis\Jsonl\ProductRowMapper;
use CatFlow\Analysis\Models\DatasetSchema;
use CatFlow\File\Services\Parsers\DatasetParserFactory;

/**
 * Builds the "example detected product" preview shown to the user after
 * analysis — the first dataset row, mapped through the (possibly
 * unconfirmed) schema, e.g. ['price' => 450.0, 'name' => '...'].
 */
class ProductPreviewBuilder
{
    public function __construct(
        private readonly DatasetParserFactory $parsers,
        private readonly ProductRowMapper $mapper,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(DatasetSchema $schema): array
    {
        $dataset = $schema->dataset;

        foreach ($this->parsers->make($dataset)->rows($dataset) as $firstRow) {
            return $this->mapper->map($firstRow, $schema->columns);
        }

        return [];
    }
}
