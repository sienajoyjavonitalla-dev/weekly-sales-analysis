<?php

namespace App\Domain\WeeklyAnalysis\Exports\Services;

use App\Domain\WeeklyAnalysis\Imports\Enums\WorkbookType;
use App\Models\GeneratedReport;
use App\Models\ImportBatch;
use App\Models\ProductCategory;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class WeeklyMeterReportExporter extends AbstractTemplateReportExporter
{
    public function __construct(
        private readonly TemplatePathResolver $templatePathResolver,
        private readonly GeneratedReportRecorder $recorder,
    ) {
    }

    public function export(ImportBatch $importBatch, ?int $generatedByUserId = null): GeneratedReport
    {
        $templatePath = $this->templatePathResolver->resolve($importBatch, WorkbookType::WeeklyMeterReport->value);

        if ($templatePath === null) {
            throw new RuntimeException('Weekly Meter Report template was not found for this import batch.');
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $this->currentMonthSheet($spreadsheet, $importBatch);

        if ($sheet === null) {
            throw new RuntimeException('Current month sheet was not found in the Weekly Meter Report template.');
        }

        $columns = $this->weekColumns($sheet, $importBatch);
        $writtenRows = $this->writeCategoryTotals($sheet, $importBatch, $columns);

        return $this->recorder->save(
            importBatch: $importBatch,
            reportType: WorkbookType::WeeklyMeterReport->value,
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
            ->whereNotNull('weekly_meter_row_label')
            ->get()
            ->keyBy('code');
        $writtenRows = 0;

        foreach ($categoryTotals as $total) {
            $category = $categories->get($total['product_category_code'] ?? null);

            if ($category === null) {
                continue;
            }

            $row = $this->findLabelRow($sheet, $category->weekly_meter_row_label, 6, 42);

            if ($row === null) {
                continue;
            }

            $sheet->setCellValue($columns['units'].$row, (float) ($total['quantity'] ?? 0));
            $sheet->setCellValue($columns['amount'].$row, (float) ($total['amount'] ?? 0));
            $writtenRows++;
        }

        return $writtenRows;
    }
}
