<?php

namespace CatFlow\Analysis\Jsonl;

use Carbon\Carbon;
use CatFlow\Analysis\Models\DatasetColumn;
use Throwable;

/**
 * Deterministically turns one raw dataset row into a normalized product
 * array, keyed by canonical field. No AI involved here — the schema (which
 * column maps to which field/type) has already been decided by
 * SchemaAnalyzer; this just applies it, row by row.
 */
class ProductRowMapper
{
    /**
     * @var array<string, string>
     */
    private const CURRENCY_MARKERS = [
        '₴' => 'UAH',
        'грн' => 'UAH',
        'uah' => 'UAH',
        '$' => 'USD',
        'usd' => 'USD',
        '€' => 'EUR',
        'eur' => 'EUR',
        '£' => 'GBP',
        'gbp' => 'GBP',
    ];

    /**
     * @param  array<string, mixed>  $rawRow
     * @param  iterable<DatasetColumn>  $columns
     * @return array<string, mixed>
     */
    public function map(array $rawRow, iterable $columns): array
    {
        $product = [];

        foreach ($columns as $column) {
            if ($column->canonical_field === null) {
                continue;
            }

            $raw = $rawRow[$column->source_column] ?? null;
            $raw = is_string($raw) ? trim($raw) : $raw;

            $product[$column->canonical_field] = match ($column->data_type) {
                DatasetColumn::TYPE_CURRENCY => $this->toCurrency($raw, $product),
                DatasetColumn::TYPE_NUMBER => $this->toNumber($raw),
                DatasetColumn::TYPE_DATE => $this->toDate($raw),
                DatasetColumn::TYPE_BOOLEAN => $this->toBoolean($raw),
                default => $raw === '' ? null : $raw,
            };
        }

        return $product;
    }

    /**
     * @param  array<string, mixed>  $product
     */
    private function toCurrency(mixed $raw, array &$product): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $string = (string) $raw;

        foreach (self::CURRENCY_MARKERS as $marker => $code) {
            if (mb_stripos($string, $marker) !== false) {
                $product['currency'] ??= $code;

                break;
            }
        }

        return $this->toNumber($string);
    }

    private function toNumber(mixed $raw): int|float|null
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $string = trim(preg_replace('/[^\d,.\-]/u', '', (string) $raw) ?? '');

        if ($string === '') {
            return null;
        }

        if (str_contains($string, ',') && str_contains($string, '.')) {
            $string = str_replace('.', '', $string);
            $string = str_replace(',', '.', $string);
        } elseif (str_contains($string, ',')) {
            $string = str_replace(',', '.', $string);
        }

        return str_contains($string, '.') ? (float) $string : (int) $string;
    }

    private function toDate(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $raw)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private function toBoolean(mixed $raw): ?bool
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $normalized = mb_strtolower(trim((string) $raw));

        return match ($normalized) {
            'так', 'yes', 'true', '1', '+' => true,
            'ні', 'no', 'false', '0', '-' => false,
            default => null,
        };
    }
}
