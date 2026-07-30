<?php

namespace CatFlow\Admin\Http\Requests\Batch;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBatchRequest extends FormRequest
{
    /**
     * Models selectable in the create-batch form. Duplicated here and in
     * batches/create.blade.php until an OpenAI package owns this list.
     *
     * @return array<int, string>
     */
    public static function allowedModels(): array
    {
        return ['gpt-4.1', 'gpt-4.1-mini', 'gpt-4o', 'gpt-4o-mini', 'o3', 'o3-mini'];
    }

    /**
     * Output formats selectable in the create-batch form.
     *
     * @return array<int, string>
     */
    public static function allowedOutputFormats(): array
    {
        return ['csv', 'xlsx', 'json', 'google_sheet'];
    }

    /**
     * The create-batch form always submits both the file input and the
     * Google Sheets URL input in the same multipart request — whichever tab
     * isn't active is merely hidden via x-show, not removed from the DOM —
     * so the inactive text input arrives as an empty string rather than
     * being absent. Normalize it to null so the "unused" field doesn't fail
     * its own format rule (e.g. `url`) and silently block submission.
     */
    protected function prepareForValidation(): void
    {
        if ($this->google_sheet_url === '') {
            $this->merge(['google_sheet_url' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'model' => ['required', 'string', Rule::in(self::allowedModels())],
            'output_format' => ['required', 'string', Rule::in(self::allowedOutputFormats())],
            'dataset' => [
                'required_without:google_sheet_url',
                'prohibits:google_sheet_url',
                'file',
                'mimes:csv,xlsx,xls,json',
                'max:25600',
            ],
            'google_sheet_url' => [
                'nullable',
                'required_without:dataset',
                'prohibits:dataset',
                'url',
            ],
            'prompt' => ['required', 'string'],
        ];
    }

    /**
     * Custom messages for the dataset/google_sheet_url mutual-exclusion
     * rules — the default "prohibits"/"required_without" wording doesn't
     * explain to the user which of the two source fields to clear.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dataset.required_without' => 'Upload a file, or paste a Google Sheets link instead.',
            'dataset.prohibits' => 'You provided both a file and a Google Sheets link — please use only one.',
            'google_sheet_url.required_without' => 'Paste a Google Sheets link, or upload a file instead.',
            'google_sheet_url.prohibits' => 'You provided both a Google Sheets link and a file — please use only one.',
        ];
    }
}
