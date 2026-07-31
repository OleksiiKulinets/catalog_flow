<?php

namespace CatFlow\Analysis\Jsonl;

/**
 * Merges the user's free-text batch prompt with one product's normalized
 * data. v1 just appends a "Product data:" block rather than substituting
 * {{placeholder}} tokens — the Prompt package doesn't have a template
 * model/UI yet to define that syntax against.
 */
class PromptTemplateRenderer
{
    /**
     * @param  array<string, mixed>  $product
     */
    public function render(string $userPrompt, array $product): string
    {
        $lines = [];

        foreach ($product as $field => $value) {
            $lines[] = "{$field}: ".$this->formatValue($value);
        }

        return trim($userPrompt)."\n\nProduct data:\n".implode("\n", $lines);
    }

    private function formatValue(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            is_bool($value) => $value ? 'yes' : 'no',
            default => (string) $value,
        };
    }
}
