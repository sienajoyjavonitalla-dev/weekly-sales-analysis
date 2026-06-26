<?php

namespace App\Http\Requests;

use App\Models\MappingRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMappingRuleRequest extends FormRequest
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
        /** @var MappingRule|null $mappingRule */
        $mappingRule = $this->route('mappingRule');

        return [
            'product_category_id' => ['sometimes', 'nullable', 'integer', 'exists:product_categories,id'],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('mapping_rules', 'name')->ignore($mappingRule?->id),
            ],
            'source_type' => ['sometimes', 'required', 'string', 'max:100'],
            'match_field' => ['sometimes', 'required', Rule::in([
                'item_id',
                'description',
                'customer_id',
                'customer_name',
                'invoice_number',
                'sales_rep_id',
                'country',
                'bill_to_state',
            ])],
            'match_operator' => ['sometimes', 'required', Rule::in(['exact', 'starts_with', 'ends_with', 'contains', 'regex'])],
            'pattern' => ['sometimes', 'required', 'string', 'max:500'],
            'target_bucket' => ['sometimes', 'nullable', 'string', 'max:100'],
            'priority' => ['sometimes', 'required', 'integer', 'min:1', 'max:65535'],
            'is_active' => ['sometimes', 'boolean'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
