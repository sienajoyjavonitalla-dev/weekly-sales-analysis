<?php

namespace App\Domain\WeeklyAnalysis\Exports\Services;

use App\Domain\WeeklyAnalysis\Imports\Enums\WorkbookType;
use App\Models\GeneratedReport;
use App\Models\ImportBatch;
use App\Models\ProductCategory;
use App\Models\ReportTotal;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class TotalSalesReportExporter extends AbstractTemplateReportExporter
{
    public function __construct(
        private readonly TemplatePathResolver $templatePathResolver,
        private readonly GeneratedReportRecorder $recorder,
    ) {
    }

    public function export(ImportBatch $importBatch, ?int $generatedByUserId = null): GeneratedReport
    {
        $templatePath = $this->templatePathResolver->resolve($importBatch, WorkbookType::TotalSalesReport->value);

        if ($templatePath === null) {
            throw new RuntimeException('Total Sales Report template was not found for this import batch.');
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $this->currentMonthSheet($spreadsheet, $importBatch);

        if ($sheet === null) {
            throw new RuntimeException('Current month sheet was not found in the Total Sales Report template.');
        }

        $columns = $this->weekColumns($sheet, $importBatch);
        $writtenRows = $this->writeCategoryTotals($sheet, $importBatch, $columns);
        $this->writeOrderMetrics($sheet, $importBatch);

        return $this->recorder->save(
            importBatch: $importBatch,
            reportType: WorkbookType::TotalSalesReport->value,
            spreadsheet: $spreadsheet,
            generatedByUserId: $generatedByUserId,
            summary: [
                'sheet' => $sheet->getTitle(),
                'week_units_column' => $columns['units'],
                'week_amount_column' => $columns['amount'],
                'category_rows_written' => $writtenRows,
            ],
        );
    }

    /**
     * @param  array{units:string, amount:string}  $columns
     */
    private function writeCategoryTotals($sheet, ImportBatch $importBatch, array $columns): int
    {
        $categoryTotals = $importBatch->reconciliationResult?->category_totals ?? [];
        $categories = ProductCategory::query()
            ->whereNotNull('total_sales_row_label')
            ->get()
            ->keyBy('code');
        $writtenRows = 0;

        foreach ($categoryTotals as $total) {
            $category = $categories->get($total['product_category_code'] ?? null);

            if ($category === null) {
                continue;
            }

            $row = $this->findLabelRow($sheet, $category->total_sales_row_label, 6, 24);

            if ($row === null) {
                continue;
            }

            $sheet->setCellValue($columns['units'].$row, (float) ($total['quantity'] ?? 0));
            $sheet->setCellValue($columns['amount'].$row, (float) ($total['amount'] ?? 0));
            $writtenRows++;
        }

        return $writtenRows;
    }

    private function writeOrderMetrics($sheet, ImportBatch $importBatch): void
    {
        $totals = ReportTotal::query()
            ->where('import_batch_id', $importBatch->id)
            ->where('report_type', 'total_sales_report')
            ->pluck('amount', 'metric_key');

        if ($totals->has('ptd_orders')) {
            $sheet->setCellValue('F29', (float) $totals->get('ptd_orders'));
        }

        if ($totals->has('open_orders_current_month')) {
            $sheet->setCellValue('M29', (float) $totals->get('open_orders_current_month'));
        }

        if ($totals->has('open_orders_next_month')) {
            $sheet->setCellValue('M30', (float) $totals->get('open_orders_next_month'));
        }

        if ($totals->has('open_orders_future')) {
            $sheet->setCellValue('M31', (float) $totals->get('open_orders_future'));
        }
    }
}
