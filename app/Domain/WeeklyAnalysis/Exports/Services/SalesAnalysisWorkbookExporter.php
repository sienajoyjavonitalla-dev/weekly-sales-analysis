<?php

namespace App\Domain\WeeklyAnalysis\Exports\Services;

use App\Domain\WeeklyAnalysis\Imports\Support\NumericValueParser;

use App\Models\GeneratedReport;
use App\Models\ImportBatch;
use App\Models\ProductCategory;
use App\Models\SalesRow;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesAnalysisWorkbookExporter
{
    private const HEADERS = [
        'Item ID',
        'Description',
        'Customer ID',
        'Customer Name',
        'Invoice Number',
        'Sales Rep 1 ID',
        'Country',
        'Bill to State',
        'Invoice Date',
        'Qty Order Sell',
        'Amount',
    ];

    /**
     * @var array<string, float>
     */
    private const COLUMN_WIDTHS = [
        'A' => 44,
        'B' => 56,
        'C' => 12,
        'D' => 34,
        'E' => 14,
        'F' => 12,
        'G' => 8,
        'H' => 12,
        'I' => 12,
        'J' => 14,
        'K' => 14,
    ];

    /**
     * @var array<int, string>
     */
    private const MISC_CATEGORY_CODES = [
        'rhp_miscellaneous',
        'parts_tsd_rhp_miscellaneous',
    ];

    private const ACCOUNTING_NUMBER_FORMAT = '#,##0.00_);(#,##0.00)';

    private const GRAND_TOTAL_FILL = 'A9D1F7';

    private const CATEGORY_HEADER_FONT_SIZE = 12;

    public function __construct(
        private readonly GeneratedReportRecorder $recorder,
        private readonly SpreadsheetValueSanitizer $sanitizer,
    ) {
    }

    public function export(
        ImportBatch $importBatch,
        ?int $generatedByUserId = null,
        bool $includeState = false,
    ): GeneratedReport {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator(config('app.name'))
            ->setTitle('Weekly Sales Analysis '.$importBatch->week_ending->format('m-d-Y'));

        $this->writeOrganizedBucketSheet($spreadsheet->getActiveSheet(), $importBatch, 'RHP', 'rhp');
        $this->writeOrganizedBucketSheet($spreadsheet->createSheet(), $importBatch, 'Parts & TSD', 'parts_tsd');

        if ($includeState) {
            $this->writeStateSheet($spreadsheet->createSheet(), $importBatch, 'State');
        }

        $this->writeSourceSheet($spreadsheet->createSheet(), $importBatch, 'Sheet');

        return $this->recorder->save(
            importBatch: $importBatch,
            reportType: 'sales_analysis',
            spreadsheet: $spreadsheet,
            generatedByUserId: $generatedByUserId,
            summary: [
                'rhp_rows' => $this->rowCount($importBatch, 'rhp'),
                'parts_tsd_rows' => $this->rowCount($importBatch, 'parts_tsd'),
                'state_rows' => $includeState
                    ? SalesRow::query()
                        ->where('import_batch_id', $importBatch->id)
                        ->where(function ($query): void {
                            $query->where('source_bucket', 'state')
                                ->orWhereHas('statePlacement');
                        })
                        ->count()
                    : 0,
                'raw_rows' => $this->rowCount($importBatch, 'raw'),
                'source_sheet_rows' => $this->sourceSheetRowCount($importBatch),
            ],
        );
    }

    private function writeOrganizedBucketSheet(Worksheet $sheet, ImportBatch $importBatch, string $title, string $bucket): void
    {
        $sheet->setTitle($title);
        $this->writeHeaderRow($sheet);

        $categories = ProductCategory::query()
            ->where('sales_analysis_bucket', $bucket)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        /** @var Collection<int|string, Collection<int, SalesRow>> $rowsByCategory */
        $rowsByCategory = SalesRow::query()
            ->where('import_batch_id', $importBatch->id)
            ->where('source_bucket', $bucket)
            ->orderBy('source_row_number')
            ->get()
            ->groupBy(fn (SalesRow $row): int|string => $row->product_category_id ?? 'none');

        $noCategoryRows = $rowsByCategory->get('none', collect());
        $noCategoryWritten = false;
        $rowNumber = 2;
        $lastDataRow = 1;
        $sheetQuantityTotal = 0.0;
        $sheetAmountTotal = 0.0;

        foreach ($categories as $category) {
            /** @var Collection<int, SalesRow> $categoryRows */
            $categoryRows = $rowsByCategory->get($category->id, collect());

            if ($categoryRows->isEmpty()) {
                continue;
            }

            $this->writeCategoryHeaderRow($sheet, $rowNumber, $category->name);
            $rowNumber++;

            $quantityTotal = 0.0;
            $amountTotal = 0.0;

            foreach ($categoryRows as $salesRow) {
                $this->writeDataRow($sheet, $rowNumber, $salesRow, $sheetQuantityTotal, $sheetAmountTotal);
                $quantityTotal += (float) $salesRow->quantity_ordered;
                $amountTotal += (float) $salesRow->amount;
                $lastDataRow = $rowNumber;
                $rowNumber++;
            }

            $this->writeSubtotalRow(
                $sheet,
                $rowNumber,
                $quantityTotal,
                $amountTotal,
                in_array($category->code, self::MISC_CATEGORY_CODES, true),
            );
            $lastDataRow = $rowNumber;
            $rowNumber++;

            if (
                ! $noCategoryWritten
                && in_array($category->code, self::MISC_CATEGORY_CODES, true)
                && $noCategoryRows->isNotEmpty()
            ) {
                $this->writeUnassignedRowsBlock(
                    $sheet,
                    $noCategoryRows,
                    $rowNumber,
                    $lastDataRow,
                    $sheetQuantityTotal,
                    $sheetAmountTotal,
                );
                $noCategoryWritten = true;
            }
        }

        if (! $noCategoryWritten && $noCategoryRows->isNotEmpty()) {
            $this->writeUnassignedRowsBlock(
                $sheet,
                $noCategoryRows,
                $rowNumber,
                $lastDataRow,
                $sheetQuantityTotal,
                $sheetAmountTotal,
                leadingBlankRow: false,
            );
        }

        $this->finalizeSheet($sheet, $lastDataRow, $sheetQuantityTotal, $sheetAmountTotal);
    }

    private function writeUnassignedRowsBlock(
        Worksheet $sheet,
        Collection $noCategoryRows,
        int &$rowNumber,
        int &$lastDataRow,
        float &$sheetQuantityTotal,
        float &$sheetAmountTotal,
        bool $leadingBlankRow = true,
    ): void {
        if ($leadingBlankRow) {
            $rowNumber++;
        }

        $quantityTotal = 0.0;
        $amountTotal = 0.0;

        foreach ($noCategoryRows as $salesRow) {
            $this->writeDataRow($sheet, $rowNumber, $salesRow, $sheetQuantityTotal, $sheetAmountTotal);
            $quantityTotal += (float) $salesRow->quantity_ordered;
            $amountTotal += (float) $salesRow->amount;
            $lastDataRow = $rowNumber;
            $rowNumber++;
        }

        $this->writeSubtotalRow($sheet, $rowNumber, $quantityTotal, $amountTotal, true);
        $lastDataRow = $rowNumber;
        $rowNumber++;

        $rowNumber++;
    }

    private function writeSourceSheet(Worksheet $sheet, ImportBatch $importBatch, string $title): void
    {
        $sheet->setTitle($title);
        $this->writeHeaderRow($sheet);

        $rowNumber = 2;
        $lastDataRow = 1;
        $sheetQuantityTotal = 0.0;
        $sheetAmountTotal = 0.0;

        $rows = SalesRow::query()
            ->where('import_batch_id', $importBatch->id)
            ->where('source_sheet', 'Sheet')
            ->orderBy('source_row_number')
            ->get();

        foreach ($rows as $salesRow) {
            $this->writeDataRow($sheet, $rowNumber, $salesRow, $sheetQuantityTotal, $sheetAmountTotal);
            $lastDataRow = $rowNumber;
            $rowNumber++;
        }

        $this->finalizeSheet($sheet, $lastDataRow, $sheetQuantityTotal, $sheetAmountTotal);
    }

    private function writeStateSheet(Worksheet $sheet, ImportBatch $importBatch, string $title): void
    {
        $sheet->setTitle($title);
        $this->writeHeaderRow($sheet);

        $rowNumber = 2;
        $lastDataRow = 1;
        $sheetQuantityTotal = 0.0;
        $sheetAmountTotal = 0.0;

        $rows = SalesRow::query()
            ->where('import_batch_id', $importBatch->id)
            ->where(function ($query): void {
                $query->where('source_bucket', 'state')
                    ->orWhereHas('statePlacement');
            })
            ->orderBy('source_row_number')
            ->get();

        foreach ($rows as $salesRow) {
            $this->writeDataRow($sheet, $rowNumber, $salesRow, $sheetQuantityTotal, $sheetAmountTotal);
            $lastDataRow = $rowNumber;
            $rowNumber++;
        }

        $this->finalizeSheet($sheet, $lastDataRow, $sheetQuantityTotal, $sheetAmountTotal);
    }

    private function writeFlatBucketSheet(Worksheet $sheet, ImportBatch $importBatch, string $title, string $bucket): void
    {
        $sheet->setTitle($title);
        $this->writeHeaderRow($sheet);

        $rowNumber = 2;
        $lastDataRow = 1;
        $sheetQuantityTotal = 0.0;
        $sheetAmountTotal = 0.0;

        $rows = SalesRow::query()
            ->where('import_batch_id', $importBatch->id)
            ->where('source_bucket', $bucket)
            ->orderBy('source_row_number')
            ->get();

        foreach ($rows as $salesRow) {
            $this->writeDataRow($sheet, $rowNumber, $salesRow, $sheetQuantityTotal, $sheetAmountTotal);
            $lastDataRow = $rowNumber;
            $rowNumber++;
        }

        $this->finalizeSheet($sheet, $lastDataRow, $sheetQuantityTotal, $sheetAmountTotal);
    }

    private function writeHeaderRow(Worksheet $sheet): void
    {
        $sheet->fromArray(self::HEADERS, null, 'A1');

        $sheet->getStyle('A1:K1')
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FF'.self::GRAND_TOTAL_FILL);
    }

    private function finalizeSheet(
        Worksheet $sheet,
        int $lastDataRow,
        float $sheetQuantityTotal,
        float $sheetAmountTotal,
    ): void {
        if ($lastDataRow >= 2) {
            $this->writeGrandTotalRow($sheet, $lastDataRow + 1, $sheetQuantityTotal, $sheetAmountTotal);
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:K'.max(1, $lastDataRow));
        $this->applyColumnWidths($sheet);
    }

    private function writeDataRow(
        Worksheet $sheet,
        int $rowNumber,
        SalesRow $salesRow,
        float &$sheetQuantityTotal,
        float &$sheetAmountTotal,
    ): void {
        $this->writeSalesRow($sheet, $rowNumber, $salesRow);
        $sheetQuantityTotal += (float) $salesRow->quantity_ordered;
        $sheetAmountTotal += (float) $salesRow->amount;
    }

    private function writeCategoryHeaderRow(Worksheet $sheet, int $rowNumber, string $categoryName): void
    {
        $sheet->setCellValue('A'.$rowNumber, $categoryName);
        $sheet->setCellValue('B'.$rowNumber, $categoryName);
        $sheet->mergeCells('B'.$rowNumber.':K'.$rowNumber);

        $sheet->getStyle('A'.$rowNumber.':K'.$rowNumber)
            ->getFont()
            ->setBold(true)
            ->setItalic(true)
            ->setSize(self::CATEGORY_HEADER_FONT_SIZE);
    }

    private function writeGrandTotalRow(
        Worksheet $sheet,
        int $rowNumber,
        float $quantityTotal,
        float $amountTotal,
    ): void {
        $sheet->fromArray([
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $quantityTotal,
            $amountTotal,
        ], null, 'A'.$rowNumber);

        $sheet->getStyle('A'.$rowNumber.':K'.$rowNumber)
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FF'.self::GRAND_TOTAL_FILL);

        $sheet->getStyle('A'.$rowNumber.':K'.$rowNumber)
            ->getFont()
            ->setBold(true);

        $this->applyAccountingFormat($sheet, 'J', $rowNumber);
        $this->applyAccountingFormat($sheet, 'K', $rowNumber);
    }

    private function applyColumnWidths(Worksheet $sheet): void
    {
        foreach (self::COLUMN_WIDTHS as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    private function applyAccountingFormat(Worksheet $sheet, string $column, int $rowNumber): void
    {
        $sheet->getStyle($column.$rowNumber)
            ->getNumberFormat()
            ->setFormatCode(self::ACCOUNTING_NUMBER_FORMAT);
    }

    private function writeSalesRow(Worksheet $sheet, int $rowNumber, SalesRow $salesRow): void
    {
        $quantity = $this->formatQuantity($salesRow->quantity_ordered);
        $amount = $this->formatAmount($salesRow->amount);

        $sheet->fromArray([
            $this->sanitizer->sanitize($salesRow->item_id),
            $this->sanitizer->sanitize($salesRow->description),
            $this->sanitizer->sanitize($salesRow->customer_id),
            $this->sanitizer->sanitize($salesRow->customer_name),
            $this->sanitizer->sanitize($salesRow->invoice_number),
            $this->sanitizer->sanitize($salesRow->sales_rep_id),
            $this->sanitizer->sanitize($salesRow->country),
            $this->sanitizer->sanitize($salesRow->bill_to_state),
            $salesRow->invoice_date?->format('n/j/Y'),
            $quantity,
            $amount,
        ], null, 'A'.$rowNumber);

        if (is_int($quantity) || is_float($quantity)) {
            $this->applyAccountingFormat($sheet, 'J', $rowNumber);
        }

        if (is_int($amount) || is_float($amount)) {
            $this->applyAccountingFormat($sheet, 'K', $rowNumber);
        }
    }

    private function writeSubtotalRow(
        Worksheet $sheet,
        int $rowNumber,
        float $quantityTotal,
        float $amountTotal,
        bool $useNaQuantity,
    ): void {
        $sheet->fromArray([
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $useNaQuantity ? 'n/a' : $quantityTotal,
            $amountTotal,
        ], null, 'A'.$rowNumber);

        if (! $useNaQuantity) {
            $this->applyAccountingFormat($sheet, 'J', $rowNumber);
        }

        $this->applyAccountingFormat($sheet, 'K', $rowNumber);

        $sheet->getStyle('J'.$rowNumber.':K'.$rowNumber)
            ->getFont()
            ->setBold(true);
    }

    private function formatQuantity(mixed $quantity): mixed
    {
        if ($quantity === null || $quantity === '') {
            return '-';
        }

        $parsed = NumericValueParser::parse($quantity) ?? (float) $quantity;

        return $parsed === 0.0 ? '-' : $parsed;
    }

    private function formatAmount(mixed $amount): mixed
    {
        if ($amount === null || $amount === '') {
            return '-';
        }

        $parsed = NumericValueParser::parse($amount) ?? (float) $amount;

        return $parsed === 0.0 ? '-' : $parsed;
    }

    private function rowCount(ImportBatch $importBatch, string $bucket): int
    {
        return SalesRow::query()
            ->where('import_batch_id', $importBatch->id)
            ->where('source_bucket', $bucket)
            ->count();
    }

    private function sourceSheetRowCount(ImportBatch $importBatch): int
    {
        return SalesRow::query()
            ->where('import_batch_id', $importBatch->id)
            ->where('source_sheet', 'Sheet')
            ->count();
    }
}
