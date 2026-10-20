<?php

namespace App\Domain\WeeklyAnalysis\Exports\Services;

use App\Domain\WeeklyAnalysis\Classification\Services\SalesRowClassifier;
use App\Domain\WeeklyAnalysis\Reconciliation\Services\WeeklyReconciliationService;
use App\Models\ImportBatch;
use Throwable;

/**
 * Re-applies the current categories and mapping rules to every batch that already has
 * a generated Sales Analysis, then rebuilds the Sales Analysis and Meter Report files
 * so the report pages always reflect the latest configuration.
 */
class GeneratedReportRefresher
{
    public function __construct(
        private readonly SalesRowClassifier $classifier,
        private readonly WeeklyReconciliationService $reconciliationService,
        private readonly SalesAnalysisWorkbookExporter $salesAnalysisExporter,
        private readonly WeeklyMeterReportExporter $weeklyMeterReportExporter,
    ) {
    }

    /**
     * @return int Number of batches whose reports were refreshed.
     */
    public function refreshAll(?int $generatedByUserId = null): int
    {
        $batches = ImportBatch::query()
            ->whereHas('generatedReports', fn ($query) => $query
                ->where('report_type', 'sales_analysis')
                ->where('status', 'completed'))
            ->with('generatedReports')
            ->orderBy('week_ending')
            ->orderBy('id')
            ->get();

        if ($batches->isEmpty()) {
            return 0;
        }

        // Re-classifying and exporting every batch can take a while for large histories.
        @set_time_limit(0);

        // Classify every batch before exporting anything: the Meter Report of a week
        // includes the earlier weeks of the same month, so they must all be up to date first.
        $classified = $batches->filter(function (ImportBatch $batch): bool {
            try {
                $this->classifier->classifyBatch($batch);
                $this->reconciliationService->reconcile($batch->refresh());

                return true;
            } catch (Throwable $exception) {
                report($exception);

                return false;
            }
        });

        $refreshed = 0;

        foreach ($classified as $batch) {
            try {
                $this->exportBatch($batch, $generatedByUserId);
                $refreshed++;
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $refreshed;
    }

    private function exportBatch(ImportBatch $batch, ?int $generatedByUserId): void
    {
        $summary = $batch->generatedReports->firstWhere('report_type', 'sales_analysis')?->summary ?? [];
        $includeState = (bool) ($summary['include_state'] ?? (($summary['state_rows'] ?? 0) > 0));

        $this->salesAnalysisExporter->export($batch->refresh(), $generatedByUserId, $includeState);

        if ($batch->generatedReports->contains('report_type', 'weekly_meter_report')) {
            $this->weeklyMeterReportExporter->export($batch->refresh(), $generatedByUserId);
        }
    }
}
