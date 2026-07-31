<?php

namespace CatFlow\Admin\Http\Requests\Batch;

use CatFlow\Analysis\Models\DatasetColumn;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmDatasetSchemaRequest extends FormRequest
{
    /**
     * The "ignore this column" option in the mapping <select> submits an
     * empty string rather than being absent — normalize it to null so it
     * passes the nullable rule instead of failing Rule::in().
     *
     * Guards against `columns` (or an individual entry) not being an array
     * at all — e.g. a malformed request sent outside the normal form.
     * Writing to a string offset with a non-numeric key throws a TypeError
     * in PHP, so this has to be checked before the array-style write below
     * rather than left to the validation rules to reject.
     */
    protected function prepareForValidation(): void
    {
        if (! is_array($this->input('columns'))) {
            return;
        }

        $columns = collect($this->input('columns'))
            ->map(function ($edit) {
                if (! is_array($edit)) {
                    return $edit;
                }

                $edit['canonical_field'] = ($edit['canonical_field'] ?? '') === '' ? null : $edit['canonical_field'];

                return $edit;
            })
            ->all();

        $this->merge(['columns' => $columns]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'columns' => ['required', 'array'],
            'columns.*.canonical_field' => ['nullable', 'string', Rule::in(DatasetColumn::allFields())],
            'columns.*.data_type' => ['required', 'string', Rule::in(DatasetColumn::allDataTypes())],
        ];
    }
}
