<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BulkResolveUnmatchedItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'resolutions' => ['required', 'array', 'min:1'],
            'resolutions.*.item_id' => ['required', 'string', 'max:255'],
            'resolutions.*.product_category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'resolutions.*.no_category_assignment' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('resolutions', []) as $index => $resolution) {
                $noCategory = filter_var($resolution['no_category_assignment'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $hasCategory = isset($resolution['product_category_id'])
                    && $resolution['product_category_id'] !== null
                    && $resolution['product_category_id'] !== '';

                if ($noCategory && $hasCategory) {
                    $validator->errors()->add(
                        "resolutions.{$index}.product_category_id",
                        'Remove the category selection when using no category assignment.',
                    );
                }

                if (! $noCategory && ! $hasCategory) {
                    $validator->errors()->add(
                        "resolutions.{$index}.product_category_id",
                        'Select a category or enable no category assignment.',
                    );
                }
            }
        });
    }
}
