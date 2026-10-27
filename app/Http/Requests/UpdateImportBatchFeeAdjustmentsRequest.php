<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateImportBatchFeeAdjustmentsRequest extends FormRequest
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
            'marketplace' => ['required', 'string', Rule::in(['amazon', 'walmart'])],
            'fees' => ['present', 'array'],
            'fees.*.amount' => ['required', 'numeric'],
        ];
    }
}
