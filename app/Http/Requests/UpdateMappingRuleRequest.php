<?php

namespace App\Http\Requests;

use App\Models\MappingRule;
use App\Rules\MappingRuleCategoryMatchesBucket;
use App\Rules\UniqueMappingRuleSignature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'product_category_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:product_categories,id',
                new MappingRuleCategoryMatchesBucket($this->input('target_bucket', $mappingRule?->target_bucket)),
            ],
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var MappingRule|null $mappingRule */
            $mappingRule = $this->route('mappingRule');

            if ($mappingRule === null) {
                return;
            }

            $willBeActive = $this->boolean('is_active', $mappingRule->is_active);

            if (! $willBeActive) {
                return;
            }

            $signatureRule = new UniqueMappingRuleSignature(
                ignoreRuleId: $mappingRule->id,
                sourceType: $this->input('source_type', $mappingRule->source_type),
                matchField: $this->input('match_field', $mappingRule->match_field),
                matchOperator: $this->input('match_operator', $mappingRule->match_operator),
                pattern: $this->input('pattern', $mappingRule->pattern),
            );

            $signatureRule->validate('pattern', $this->input('pattern', $mappingRule->pattern), function (string $message) use ($validator): void {
                $validator->errors()->add('pattern', $message);
            });
        });
    }
}
