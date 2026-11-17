<?php

namespace App\Domain\WeeklyAnalysis\Exports\Services;

use App\Domain\WeeklyAnalysis\Storage\WorkbookStorage;
use App\Models\GeneratedReport;
use App\Models\ImportBatch;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class GeneratedReportRecorder
{
    public function __construct(
        private readonly WorkbookStorage $workbookStorage,
    ) {
    }

    public function save(
        ImportBatch $importBatch,
        string $reportType,
        Spreadsheet $spreadsheet,
        ?int $generatedByUserId = null,
        array $summary = [],
    ): GeneratedReport {
        $fileName = $this->fileName($importBatch, $reportType);
        $storagePath = 'generated-reports/'.$importBatch->id.'/'.$fileName;
        $stored = $this->workbookStorage->putSpreadsheet($storagePath, $spreadsheet);

        return GeneratedReport::query()->updateOrCreate(
            [
                'import_batch_id' => $importBatch->id,
                'report_type' => $reportType,
            ],
            [
                'status' => 'completed',
                'file_name' => $fileName,
                'storage_path' => $stored['storage_path'],
                'sha256_checksum' => $stored['sha256_checksum'],
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
