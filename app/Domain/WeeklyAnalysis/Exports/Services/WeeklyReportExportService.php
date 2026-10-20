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
        $totalSales = $this->exportTotalSalesIfTemplateUploaded($importBatch->refresh(), $generatedByUserId);
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

        // The Total Sales Report is optional: it is left out when no template was uploaded.
        return array_values(array_filter([$salesAnalysis, $totalSales, $weeklyMeter]));
    }

    /**
     * Total Sales Report needs an uploaded template. Without one there is nothing to generate,
     * so it is skipped (and any stale "template not found" failure from earlier runs is cleared)
     * instead of being reported as a failure on every upload.
     */
    private function exportTotalSalesIfTemplateUploaded(ImportBatch $importBatch, ?int $generatedByUserId): ?GeneratedReport
    {
        if (! $this->totalSalesReportExporter->hasTemplate($importBatch)) {
            GeneratedReport::query()
                ->where('import_batch_id', $importBatch->id)
                ->where('report_type', 'total_sales_report')
                ->where('status', 'failed')
                ->delete();

            return null;
        }

        return $this->attemptTemplateExport($importBatch, 'total_sales_report', $generatedByUserId);
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
