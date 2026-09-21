<?php

use App\Domain\WeeklyAnalysis\Classification\Services\SalesRowClassifier;
use App\Domain\WeeklyAnalysis\Exports\Services\WeeklyReportExportService;
use App\Domain\WeeklyAnalysis\Imports\Enums\WorkbookType;
use App\Domain\WeeklyAnalysis\Imports\Services\WeeklyWorkbookSetValidator;
use App\Domain\WeeklyAnalysis\Reconciliation\Services\WeeklyReconciliationService;
use App\Models\ImportBatch;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Artisan::command('db:fix-product-category-fks', function (): int {
    if (DB::getDriverName() !== 'mysql') {
        $this->error('This command only supports MySQL.');

        return self::FAILURE;
    }

    if (! Schema::hasTable('product_categories')) {
        $this->error('Table product_categories does not exist.');

        return self::FAILURE;
    }

    $idColumnType = DB::table('information_schema.COLUMNS')
        ->where('TABLE_SCHEMA', DB::getDatabaseName())
        ->where('TABLE_NAME', 'product_categories')
        ->where('COLUMN_NAME', 'id')
        ->value('COLUMN_TYPE');

    if (! is_string($idColumnType) || $idColumnType === '') {
        $this->error('Unable to read product_categories.id column type.');

        return self::FAILURE;
    }

    $hasUniqueId = DB::table('information_schema.STATISTICS')
        ->where('TABLE_SCHEMA', DB::getDatabaseName())
        ->where('TABLE_NAME', 'product_categories')
        ->where('COLUMN_NAME', 'id')
        ->where('NON_UNIQUE', 0)
        ->exists();

    if (! $hasUniqueId) {
        $this->warn('product_categories: adding PRIMARY KEY on id');
        DB::statement('ALTER TABLE `product_categories` ADD PRIMARY KEY (`id`)');
    }

    $tables = ['mapping_rules', 'sales_rows'];

    if (Schema::hasTable('sales_row_state_placements')) {
        $tables[] = 'sales_row_state_placements';
    }

    $database = DB::getDatabaseName();

    foreach ($tables as $table) {
        $constraint = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', 'product_category_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->first(['CONSTRAINT_NAME', 'REFERENCED_TABLE_NAME']);

        if ($constraint !== null) {
            $this->line("{$table}: dropping FK {$constraint->CONSTRAINT_NAME} -> {$constraint->REFERENCED_TABLE_NAME}");

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropForeign(['product_category_id']);
            });
        }

        DB::statement("ALTER TABLE `{$table}` MODIFY `product_category_id` {$idColumnType} NULL");
        $this->line("{$table}: aligned product_category_id to {$idColumnType}");

        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->foreign('product_category_id')
                ->references('id')
                ->on('product_categories')
                ->nullOnDelete();
        });

        $this->info("{$table}: FK now references product_categories");
    }

    return self::SUCCESS;
})->purpose('Point product_category_id foreign keys at product_categories');

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
    })->purpose('Validate the weekly sales analysis workbooks');

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
