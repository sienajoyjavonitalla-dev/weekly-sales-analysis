<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadWeeklyWorkbookSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAnalyst() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKilobytes = (int) config('weekly-analysis.uploads.max_file_size_kb');
        $fileRule = ['nullable', 'file', 'mimes:xlsx', "max:{$maxKilobytes}"];

        return [
            'import_batch_id' => ['nullable', 'integer', 'exists:import_batches,id'],
            'week_ending' => ['nullable', 'date'],
            'sales_analysis' => $fileRule,
            'income_statement' => $fileRule,
            'total_sales_report' => $fileRule,
            'weekly_meter_report' => $fileRule,
            'open_orders' => $fileRule,
            'ptd_orders' => $fileRule,
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $hasWorkbook = collect(config('weekly-analysis.uploads.required_file_types'))
                ->contains(fn (string $type): bool => $this->hasFile($type));

            if (! $hasWorkbook) {
                $validator->errors()->add('workbooks', 'Upload at least one workbook.');
            }
        });
    }
}
