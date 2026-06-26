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
        $fileRule = ['required', 'file', 'mimes:xlsx', "max:{$maxKilobytes}"];

        return [
            'week_ending' => ['required', 'date'],
            'sales_analysis' => $fileRule,
            'income_statement' => $fileRule,
            'total_sales_report' => $fileRule,
            'weekly_meter_report' => $fileRule,
            'open_orders' => $fileRule,
            'ptd_orders' => $fileRule,
        ];
    }
}
