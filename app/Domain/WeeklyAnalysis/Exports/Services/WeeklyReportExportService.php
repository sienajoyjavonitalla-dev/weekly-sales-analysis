<?php

namespace App\Domain\WeeklyAnalysis\Exports\Services;

use App\Domain\WeeklyAnalysis\Classification\Services\SalesRowClassifier;
use App\Domain\WeeklyAnalysis\Reconciliation\Services\WeeklyReconciliationService;
use App\Models\GeneratedReport;
use App\Models\ImportBatch;
use Illuminate\Support\Collection;
use Throwable;

class WeeklyReportExportService
{
    public function __construct(
        private readonly SalesRowClassifier $classifier,
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
        $monthBatches = $this->batchesInSameMonth($importBatch);

        foreach ($monthBatches as $batch) {
            $this->classifier->classifyBatch($batch);
        }

        $this->reconciliationService->reconcile($importBatch->refresh());

        $salesAnalysis = $this->salesAnalysisExporter->export(
            $importBatch->refresh(),
            $generatedByUserId,
            $includeState,
        );
        $totalSales = $this->attemptTemplateExport(
            $importBatch->refresh(),
            'total_sales_report',
            $generatedByUserId,
        );
        $weeklyMeter = $this->attemptTemplateExport(
            $importBatch->refresh(),
            'weekly_meter_report',
            $generatedByUserId,
        );

        foreach ($monthBatches as $sibling) {
            if ($sibling->id === $importBatch->id) {
                continue;
            }

            $this->attemptTemplateExport(
                $sibling->refresh(),
                'weekly_meter_report',
                $generatedByUserId,
            );
        }

        return [$salesAnalysis, $totalSales, $weeklyMeter];
    }

    /**
     * @return Collection<int, ImportBatch>
     */
    private function batchesInSameMonth(ImportBatch $importBatch): Collection
    {
        $weekEnding = $importBatch->week_ending;

        return ImportBatch::query()
            ->whereYear('week_ending', $weekEnding->year)
            ->whereMonth('week_ending', $weekEnding->month)
            ->orderBy('week_ending')
            ->get();
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
