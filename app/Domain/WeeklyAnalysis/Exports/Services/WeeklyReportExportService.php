<?php

namespace App\Domain\WeeklyAnalysis\Exports\Services;

use App\Domain\WeeklyAnalysis\Reconciliation\Services\WeeklyReconciliationService;
use App\Models\GeneratedReport;
use App\Models\ImportBatch;
use Throwable;

class WeeklyReportExportService
{
    public function __construct(
        private readonly WeeklyReconciliationService $reconciliationService,
        private readonly SalesAnalysisWorkbookExporter $salesAnalysisExporter,
        private readonly TotalSalesReportExporter $totalSalesReportExporter,
        private readonly WeeklyMeterReportExporter $weeklyMeterReportExporter,
    ) {
    }

    /**
     * @return array<int, GeneratedReport>
     */
    public function exportAll(
        ImportBatch $importBatch,
        ?int $generatedByUserId = null,
        bool $includeState = false,
    ): array {
        $this->reconciliationService->reconcile($importBatch);

        return [
            $this->salesAnalysisExporter->export($importBatch->refresh(), $generatedByUserId, $includeState),
            $this->attemptTemplateExport($importBatch->refresh(), 'total_sales_report', $generatedByUserId),
            $this->attemptTemplateExport($importBatch->refresh(), 'weekly_meter_report', $generatedByUserId),
        ];
    }

    private function attemptTemplateExport(ImportBatch $importBatch, string $reportType, ?int $generatedByUserId): GeneratedReport
    {
        try {
            return match ($reportType) {
                'total_sales_report' => $this->totalSalesReportExporter->export($importBatch, $generatedByUserId),
                'weekly_meter_report' => $this->weeklyMeterReportExporter->export($importBatch, $generatedByUserId),
                default => throw new \InvalidArgumentException("Unsupported report type [{$reportType}]."),
            };
        } catch (Throwable $exception) {
            return GeneratedReport::query()->updateOrCreate(
                [
                    'import_batch_id' => $importBatch->id,
                    'report_type' => $reportType,
                ],
                [
                    'status' => 'failed',
                    'file_name' => null,
                    'storage_path' => null,
                    'sha256_checksum' => null,
                    'summary' => null,
                    'errors' => [
                        ['message' => $exception->getMessage()],
                    ],
                    'generated_by_user_id' => $generatedByUserId,
                    'generated_at' => now(),
                ],
            );
        }
    }
}
