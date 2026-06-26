<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMappingRuleRequest extends FormRequest
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
            'product_category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'name' => ['required', 'string', 'max:255', 'unique:mapping_rules,name'],
            'source_type' => ['required', 'string', 'max:100'],
            'match_field' => ['required', Rule::in([
                'item_id',
                'description',
                'customer_id',
                'customer_name',
                'invoice_number',
                'sales_rep_id',
                'country',
                'bill_to_state',
            ])],
            'match_operator' => ['required', Rule::in(['exact', 'starts_with', 'ends_with', 'contains', 'regex'])],
            'pattern' => ['required', 'string', 'max:500'],
            'target_bucket' => ['nullable', 'string', 'max:100'],
            'priority' => ['required', 'integer', 'min:1', 'max:65535'],
            'is_active' => ['sometimes', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
