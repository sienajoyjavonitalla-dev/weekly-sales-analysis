<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'resolutions.*.product_category_id' => ['required', 'integer', 'exists:product_categories,id'],
        ];
    }
}
