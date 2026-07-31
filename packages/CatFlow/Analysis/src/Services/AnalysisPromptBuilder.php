<?php

namespace CatFlow\Analysis\Services;

use CatFlow\Analysis\Models\DatasetColumn;

class AnalysisPromptBuilder
{
    /**
     * Build the chat messages + structured-output schema asking the model to
     * map each dataset column onto the fixed product-field vocabulary
     * (DatasetColumn::allFields()). Strict JSON-schema mode is used instead
     * of free-text parsing so the response never needs a lenient parser.
     *
     * @param  array<int, string>  $columns
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{messages: array<int, array{role: string, content: string}>, response_format: array<string, mixed>}
     */
    public function build(array $columns, array $rows): array
    {
        $fields = implode(', ', DatasetColumn::allFields());
        $types = implode(', ', DatasetColumn::allDataTypes());

        $system = <<<PROMPT
            You analyze the structure of a product data file for an e-commerce catalog.

            For every column header given, decide which single canonical product field it
            represents, or that it does not map to any of them.

            Allowed canonical fields (use exactly one of these, or null if the column is
            irrelevant/unmappable): {$fields}.

            Allowed data types (use exactly one per column): {$types}.

            Rules:
            - Look at both the header text and the sample values to decide.
            - Never invent a canonical field name outside the allowed list.
            - If two columns could map to the same field, pick the better match and set the
              other's canonical_field to null.
            - confidence is your certainty for that single column, from 0.0 to 1.0.
            - overall_confidence is your certainty across the whole mapping, from 0.0 to 1.0.
            - example_value must be copied verbatim from one of the sample rows (or null if
              every sample value for that column is empty).
            PROMPT;

        $user = "Columns: ".implode(', ', $columns)
            ."\n\nSample rows (JSON): ".json_encode($rows, JSON_UNESCAPED_UNICODE);

        return [
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'response_format' => $this->responseSchema(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function responseSchema(): array
    {
        return [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'dataset_column_mapping',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'columns' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'source_column' => ['type' => 'string'],
                                    'canonical_field' => [
                                        'type' => ['string', 'null'],
                                        'enum' => [...DatasetColumn::allFields(), null],
                                    ],
                                    'data_type' => [
                                        'type' => 'string',
                                        'enum' => DatasetColumn::allDataTypes(),
                                    ],
                                    'confidence' => ['type' => 'number'],
                                    'example_value' => ['type' => ['string', 'null']],
                                ],
                                'required' => ['source_column', 'canonical_field', 'data_type', 'confidence', 'example_value'],
                                'additionalProperties' => false,
                            ],
                        ],
                        'overall_confidence' => ['type' => 'number'],
                    ],
                    'required' => ['columns', 'overall_confidence'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }
}
