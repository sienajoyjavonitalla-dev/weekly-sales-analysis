<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMarketplaceFeeRequest extends FormRequest
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
            'marketplace' => ['required', 'string', 'max:100'],
            'fee_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'between:-999999999999.99,999999999999.99'],
        ];
    }
}
