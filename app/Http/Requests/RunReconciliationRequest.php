<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RunReconciliationRequest extends FormRequest
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
            'tolerance' => ['sometimes', 'numeric', 'min:0', 'max:1000'],
        ];
    }
}
