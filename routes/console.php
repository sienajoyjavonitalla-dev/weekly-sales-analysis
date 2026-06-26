<?php

use App\Domain\WeeklyAnalysis\Classification\Services\SalesRowClassifier;
use App\Domain\WeeklyAnalysis\Exports\Services\WeeklyReportExportService;
use App\Domain\WeeklyAnalysis\Imports\Enums\WorkbookType;
use App\Domain\WeeklyAnalysis\Imports\Services\WeeklyWorkbookSetValidator;
use App\Domain\WeeklyAnalysis\Reconciliation\Services\WeeklyReconciliationService;
use App\Models\ImportBatch;
use Illuminate\Support\Facades\Artisan;

Artisan::command('about:weekly-sales', function (): void {
    $this->info('Weekly Sales Analysis automation scaffold is installed.');
})->purpose('Display weekly sales analysis scaffold information');

Artisan::command('weekly-analysis:validate-workbooks
    {--sales-analysis= : Path to the weekly Sales Analysis workbook}
    {--income-statement= : Path to the Income Statement workbook}
    {--total-sales-report= : Path to the Total Sales Report template}
    {--weekly-meter-report= : Path to the Weekly Meter Report template}
    {--open-orders= : Path to the Open Orders workbook}
    {--ptd-orders= : Path to the PTD Orders workbook}', function (WeeklyWorkbookSetValidator $validator): int {
        $result = $validator->validate([
            WorkbookType::SalesAnalysis->value => (string) $this->option('sales-analysis'),
            WorkbookType::IncomeStatement->value => (string) $this->option('income-statement'),
            WorkbookType::TotalSalesReport->value => (string) $this->option('total-sales-report'),
            WorkbookType::WeeklyMeterReport->value => (string) $this->option('weekly-meter-report'),
            WorkbookType::OpenOrders->value => (string) $this->option('open-orders'),
            WorkbookType::PtdOrders->value => (string) $this->option('ptd-orders'),
        ]);

        $this->line(json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $result->isValid() ? self::SUCCESS : self::FAILURE;
    })->purpose('Validate the six weekly sales analysis workbooks');

Artisan::command('weekly-analysis:classify-sales-rows {importBatchId : Import batch ID to classify}', function (
    int $importBatchId,
    SalesRowClassifier $classifier,
): int {
    $importBatch = ImportBatch::query()->find($importBatchId);

    if ($importBatch === null) {
        $this->error("Import batch [{$importBatchId}] was not found.");

        return self::FAILURE;
    }

    $summary = $classifier->classifyBatch($importBatch);

    $this->info('Sales row classification complete.');
    $this->table(['Matched', 'Unmatched', 'Total'], [[
        $summary['matched'],
        $summary['unmatched'],
        $summary['total'],
    ]]);

    return self::SUCCESS;
})->purpose('Apply active mapping rules to imported sales rows for a batch');

Artisan::command('weekly-analysis:reconcile
    {importBatchId : Import batch ID to reconcile}
    {--tolerance=0.01 : Maximum allowed difference before the batch is marked unbalanced}', function (
        int $importBatchId,
        WeeklyReconciliationService $reconciliationService,
    ): int {
        $importBatch = ImportBatch::query()->find($importBatchId);

        if ($importBatch === null) {
            $this->error("Import batch [{$importBatchId}] was not found.");

            return self::FAILURE;
        }

        $summary = $reconciliationService->reconcile(
            importBatch: $importBatch,
            tolerance: (float) $this->option('tolerance'),
        );

        $result = $summary->result;

        $this->info('Weekly reconciliation complete.');
        $this->table([
            'Sales Analysis',
            'Income Statement',
            'Marketplace Fees',
            'Adjusted IS',
            'Difference',
            'Balanced',
        ], [[
            $result->sales_analysis_total,
            $result->income_statement_total,
            $result->marketplace_fee_total,
            $result->adjusted_income_statement_total,
            $result->difference,
            $result->is_balanced ? 'yes' : 'no',
        ]]);

        return $result->is_balanced ? self::SUCCESS : self::FAILURE;
    })->purpose('Calculate category totals, order totals, and reconciliation for a batch');

Artisan::command('weekly-analysis:export-reports {importBatchId : Import batch ID to export}', function (
    int $importBatchId,
    WeeklyReportExportService $exportService,
): int {
    $importBatch = ImportBatch::query()->find($importBatchId);

    if ($importBatch === null) {
        $this->error("Import batch [{$importBatchId}] was not found.");

        return self::FAILURE;
    }

    $reports = $exportService->exportAll($importBatch);

    $this->info('Weekly report export complete.');
    $this->table(['Report Type', 'Status', 'File'], array_map(static fn ($report): array => [
        $report->report_type,
        $report->status,
        $report->file_name ?? 'n/a',
    ], $reports));

    return collect($reports)->contains(fn ($report): bool => $report->status === 'failed')
        ? self::FAILURE
        : self::SUCCESS;
})->purpose('Generate Sales Analysis, Total Sales, and Weekly Meter Excel reports');
