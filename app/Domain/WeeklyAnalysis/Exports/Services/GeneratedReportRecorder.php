<?php

namespace App\Domain\WeeklyAnalysis\Exports\Services;

use App\Models\GeneratedReport;
use App\Models\ImportBatch;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class GeneratedReportRecorder
{
    public function save(
        ImportBatch $importBatch,
        string $reportType,
        Spreadsheet $spreadsheet,
        ?int $generatedByUserId = null,
        array $summary = [],
    ): GeneratedReport {
        $directory = storage_path('app/private/generated-reports/'.$importBatch->id);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $fileName = $this->fileName($importBatch, $reportType);
        $absolutePath = $directory.DIRECTORY_SEPARATOR.$fileName;

        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($absolutePath);

        return GeneratedReport::query()->updateOrCreate(
            [
                'import_batch_id' => $importBatch->id,
                'report_type' => $reportType,
            ],
            [
                'status' => 'completed',
                'file_name' => $fileName,
                'storage_path' => 'generated-reports/'.$importBatch->id.'/'.$fileName,
                'sha256_checksum' => hash_file('sha256', $absolutePath),
                'summary' => $summary,
                'errors' => null,
                'generated_by_user_id' => $generatedByUserId,
                'generated_at' => now(),
            ],
        );
    }

    private function fileName(ImportBatch $importBatch, string $reportType): string
    {
        $date = $importBatch->week_ending->format('Y-m-d');

        return "{$date}-{$reportType}.xlsx";
    }
}
