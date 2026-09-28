<?php

namespace App\Http\Requests;

use App\Domain\WeeklyAnalysis\Reports\WeeklyMeterModelCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWeeklyMeterAdditionalFeesRequest extends FormRequest
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
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'model_label' => ['required', 'string', Rule::in(WeeklyMeterModelCatalog::labels())],
            'fees' => ['present', 'array'],
            'fees.*.amount' => ['required', 'numeric'],
        ];
    }
}
