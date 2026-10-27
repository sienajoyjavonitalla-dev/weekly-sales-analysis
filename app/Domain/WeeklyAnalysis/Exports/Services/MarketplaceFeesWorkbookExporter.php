<?php

namespace App\Domain\WeeklyAnalysis\Exports\Services;

use App\Domain\WeeklyAnalysis\Imports\Enums\WorkbookType;
use App\Domain\WeeklyAnalysis\Imports\Support\NumericValueParser;
use App\Domain\WeeklyAnalysis\Reports\FeesFamilyCatalog;
use App\Models\GeneratedReport;
use App\Models\ImportBatch;
use App\Models\ImportBatchFeeAdjustment;
use App\Models\ProductCategory;
use App\Models\SalesRow;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class MarketplaceFeesWorkbookExporter
{
    private const HEADERS = [
        'A' => 'Sales Rep 1 ID',
        'B' => 'Item ID',
        'C' => 'Description',
        'D' => 'Amount',
        'E' => null,
        'F' => null,
        'G' => 'Invoice Date',
        'H' => 'Invoice Number',
        'I' => 'Customer PO Number',
        'J' => 'Ship to State',
        'K' => 'Customer ID',
        'L' => 'Notes',
    ];

    /**
     * @var array<string, float>
     */
    private const COLUMN_WIDTHS = [
        'A' => 16,
        'B' => 18,
        'C' => 56,
        'D' => 14,
        'E' => 12,
        'F' => 14,
        'G' => 14,
        'H' => 16,
        'I' => 28,
        'J' => 14,
        'K' => 14,
        'L' => 40,
    ];

    private const ACCOUNTING_NUMBER_FORMAT = '#,##0.00_);(#,##0.00)';

    private const PERCENT_FORMAT = '0.00%';

    private const HEADER_FILL = 'A9D1F7';

    private const CATEGORY_HEADER_FILL = 'FFE699';

    private const CATEGORY_HEADER_FONT_SIZE = 12;

    /**
     * Amazon Fees folds these categories into a single RHP MISCELLANEOUS group.
     *
     * @var list<string>
     */
    private const AMAZON_COMBINED_RHP_MISCELLANEOUS_CODES = [
        'rhp_miscellaneous',
        'rhp_miscellaneous_l6',
        'misc_on_total_sales_report',
    ];

    private const AMAZON_COMBINED_RHP_MISCELLANEOUS_NAMES = [
        'rhp miscellaneous',
        'rhp miscellaneous l6',
        'misc (on total sales report)',
    ];

    public function __construct(
        private readonly GeneratedReportRecorder $recorder,
        private readonly SpreadsheetValueSanitizer $sanitizer,
    ) {
    }

    public function export(
        ImportBatch $importBatch,
        WorkbookType $workbookType,
        ?int $generatedByUserId = null,
    ): GeneratedReport {
        if (! $workbookType->isMarketplaceFees()) {
            throw new RuntimeException("Unsupported marketplace fees type [{$workbookType->value}].");
        }

        $spreadsheet = new Spreadsheet();
        $title = $workbookType === WorkbookType::AmazonFees ? 'Amazon Fees' : 'Walmart Fees';
        $spreadsheet->getProperties()
            ->setCreator(config('app.name'))
            ->setTitle($title.' '.$importBatch->week_ending->format('m-d-Y'));

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sheet');
        $summary = $this->writeSheet($sheet, $importBatch, $workbookType);

        return $this->recorder->save(
            importBatch: $importBatch,
            reportType: $workbookType->value,
            spreadsheet: $spreadsheet,
            generatedByUserId: $generatedByUserId,
            summary: $summary,
        );
    }

    public function hasUploadedFile(ImportBatch $importBatch, WorkbookType $workbookType): bool
    {
        return $importBatch->uploadedFiles()
            ->where('file_type', $workbookType->value)
            ->where('status', 'imported')
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function writeSheet(Worksheet $sheet, ImportBatch $importBatch, WorkbookType $workbookType): array
    {
        $this->writeHeaderRow($sheet);

        $rows = SalesRow::query()
            ->with('productCategory')
            ->forWorkbookType($workbookType->value)
            ->where('import_batch_id', $importBatch->id)
            ->orderBy('source_row_number')
            ->get();

        if ($workbookType === WorkbookType::AmazonFees) {
            $rows = $rows
                ->reject(fn (SalesRow $row): bool => $this->isExcludedShippingRow($row))
                ->values();
        }

        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $categoriesByCode = $categories->keyBy('code');

        $rowsByGroup = $rows->groupBy(
            fn (SalesRow $row): int|string => $this->exportGroupKey($row, $categoriesByCode, $workbookType)
        );

        $rowNumber = 2;
        /** @var list<array{row:int, family:string, start:int, end:int}> $subtotals */
        $subtotals = [];
        $groupCount = 0;
        $seenGroupKeys = [];

        foreach ($categories as $category) {
            $groupKey = $this->exportGroupKeyForCategory($category, $categoriesByCode, $workbookType);
            if (isset($seenGroupKeys[$groupKey])) {
                continue;
            }

            /** @var Collection<int, SalesRow> $categoryRows */
            $categoryRows = $rowsByGroup->get($groupKey, collect())
                ->sortBy('source_row_number')
                ->values();
            if ($categoryRows->isEmpty()) {
                continue;
            }

            $seenGroupKeys[$groupKey] = true;
            $headerName = $this->exportGroupName($category, $categoriesByCode, $workbookType);
            $meterLabel = $this->exportGroupMeterLabel($category, $categoriesByCode, $workbookType);

            $this->writeCategoryHeaderRow($sheet, $rowNumber, $headerName);
            $rowNumber++;

            $dataStart = $rowNumber;
            foreach ($categoryRows as $salesRow) {
                $this->writeDataRow($sheet, $rowNumber, $salesRow);
                $rowNumber++;
            }
            $dataEnd = $rowNumber - 1;

            $subtotals[] = [
                'row' => $rowNumber,
                'family' => FeesFamilyCatalog::forMeterLabel($meterLabel),
                'start' => $dataStart,
                'end' => $dataEnd,
            ];
            $this->writeAmountSubtotalPlaceholder($sheet, $rowNumber, $dataStart, $dataEnd);
            $rowNumber += 2;
            $groupCount++;
        }

        $unassigned = $rowsByGroup->get('none', collect());
        if ($unassigned->isNotEmpty()) {
            $dataStart = $rowNumber;
            foreach ($unassigned as $salesRow) {
                $this->writeDataRow($sheet, $rowNumber, $salesRow);
                $rowNumber++;
            }
            $dataEnd = $rowNumber - 1;
            $subtotals[] = [
                'row' => $rowNumber,
                'family' => FeesFamilyCatalog::RHP,
                'start' => $dataStart,
                'end' => $dataEnd,
            ];
            $this->writeAmountSubtotalPlaceholder($sheet, $rowNumber, $dataStart, $dataEnd);
            $rowNumber += 2;
            $groupCount++;
        }

        $rowNumber += 5;
        $grandRow = $rowNumber;
        $manualRow = $grandRow + 1;
        $amounts = $this->manualAmounts($importBatch, $workbookType);

        $lastContentRow = max(1, $grandRow - 1);
        $sheet->setCellValue('D'.$grandRow, '=SUM(D2:D'.$lastContentRow.')/2');
        $sheet->setCellValue('E'.$grandRow, '=SUM(E2:E'.$lastContentRow.')');
        $sheet->setCellValue('F'.$grandRow, '=SUM(F2:F'.$lastContentRow.')');
        $this->applyAccountingFormat($sheet, 'D', $grandRow);
        $this->applyPercentFormat($sheet, 'E', $grandRow);
        $this->applyAccountingFormat($sheet, 'F', $grandRow);
        $sheet->getStyle('A'.$grandRow.':L'.$grandRow)->getFont()->setBold(true);

        $formula = $this->manualAmountFormula($amounts);
        if ($formula !== null) {
            $sheet->setCellValue('F'.$manualRow, $formula);
            $this->applyAccountingFormat($sheet, 'F', $manualRow);
        }

        foreach ($subtotals as $subtotal) {
            $subRow = $subtotal['row'];
            $sheet->setCellValue('E'.$subRow, '=D'.$subRow.'/$D$'.$grandRow);
            $sheet->setCellValue('F'.$subRow, '=$F$'.$manualRow.'*E'.$subRow);
            $this->applyPercentFormat($sheet, 'E', $subRow);
            $this->applyAccountingFormat($sheet, 'F', $subRow);
        }

        $summaryStart = $manualRow + 5;
        $familyRows = $this->writeFamilySummary($sheet, $summaryStart, $subtotals);

        $this->applyColumnWidths($sheet);
        $sheet->freezePane('A2');

        return [
            'row_count' => $rows->count(),
            'group_count' => $groupCount,
            'manual_fee_total' => round(array_sum($amounts), 2),
            'grand_total_row' => $grandRow,
            'manual_amount_row' => $manualRow,
            'family_summary_rows' => $familyRows,
        ];
    }

    private function isExcludedShippingRow(SalesRow $row): bool
    {
        $itemId = strtoupper(trim((string) $row->item_id));
        $itemId = preg_replace('/[\s_\-]+/', '_', $itemId) ?? $itemId;

        return str_starts_with($itemId, 'SHIP');
    }

    /**
     * @param  Collection<string, ProductCategory>  $categoriesByCode
     */
    private function exportGroupKey(SalesRow $row, Collection $categoriesByCode, WorkbookType $workbookType): int|string
    {
        if ($row->product_category_id === null) {
            return 'none';
        }

        if ($this->combinesIntoRhpMiscellaneous($row->productCategory, $workbookType)) {
            return $categoriesByCode->get('rhp_miscellaneous')?->id ?? 'rhp_miscellaneous';
        }

        return $row->product_category_id;
    }

    /**
     * @param  Collection<string, ProductCategory>  $categoriesByCode
     */
    private function exportGroupKeyForCategory(
        ProductCategory $category,
        Collection $categoriesByCode,
        WorkbookType $workbookType,
    ): int|string {
        if ($this->combinesIntoRhpMiscellaneous($category, $workbookType)) {
            return $categoriesByCode->get('rhp_miscellaneous')?->id ?? 'rhp_miscellaneous';
        }

        return $category->id;
    }

    /**
     * @param  Collection<string, ProductCategory>  $categoriesByCode
     */
    private function exportGroupName(
        ProductCategory $category,
        Collection $categoriesByCode,
        WorkbookType $workbookType,
    ): string {
        if ($this->combinesIntoRhpMiscellaneous($category, $workbookType)) {
            return $categoriesByCode->get('rhp_miscellaneous')?->name ?? 'RHP MISCELLANEOUS';
        }

        return $category->name;
    }

    /**
     * @param  Collection<string, ProductCategory>  $categoriesByCode
     */
    private function exportGroupMeterLabel(
        ProductCategory $category,
        Collection $categoriesByCode,
        WorkbookType $workbookType,
    ): ?string {
        if ($this->combinesIntoRhpMiscellaneous($category, $workbookType)) {
            return $categoriesByCode->get('rhp_miscellaneous')?->weekly_meter_row_label
                ?? $category->weekly_meter_row_label;
        }

        return $category->weekly_meter_row_label;
    }

    private function combinesIntoRhpMiscellaneous(?ProductCategory $category, WorkbookType $workbookType): bool
    {
        if ($workbookType !== WorkbookType::AmazonFees || $category === null) {
            return false;
        }

        if (in_array($category->code, self::AMAZON_COMBINED_RHP_MISCELLANEOUS_CODES, true)) {
            return true;
        }

        return in_array(
            strtolower(trim($category->name)),
            self::AMAZON_COMBINED_RHP_MISCELLANEOUS_NAMES,
            true,
        );
    }

    private function writeHeaderRow(Worksheet $sheet): void
    {
        foreach (self::HEADERS as $column => $header) {
            if ($header !== null) {
                $sheet->setCellValue($column.'1', $header);
            }
        }

        $sheet->getStyle('A1:L1')->getFont()->setBold(true);
        $this->applyRowFill($sheet, 1, self::HEADER_FILL);
    }

    private function writeCategoryHeaderRow(Worksheet $sheet, int $rowNumber, string $categoryName): void
    {
        $sheet->setCellValue('A'.$rowNumber, $categoryName);
        $sheet->setCellValue('B'.$rowNumber, $categoryName);
        $sheet->mergeCells('B'.$rowNumber.':L'.$rowNumber);

        $sheet->getStyle('A'.$rowNumber.':L'.$rowNumber)
            ->getFont()
            ->setBold(true)
            ->setItalic(true)
            ->setSize(self::CATEGORY_HEADER_FONT_SIZE);
        $this->applyRowFill($sheet, $rowNumber, self::CATEGORY_HEADER_FILL);
    }

    private function applyRowFill(Worksheet $sheet, int $rowNumber, string $rgb): void
    {
        $sheet->getStyle('A'.$rowNumber.':L'.$rowNumber)
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FF'.$rgb);
    }

    private function writeDataRow(Worksheet $sheet, int $rowNumber, SalesRow $salesRow): void
    {
        $raw = is_array($salesRow->raw_values) ? $salesRow->raw_values : [];
        $amount = NumericValueParser::parse($salesRow->amount) ?? (float) $salesRow->amount;

        $sheet->fromArray([
            $this->sanitizer->sanitize($salesRow->sales_rep_id),
            $this->sanitizer->sanitize($salesRow->item_id),
            $this->sanitizer->sanitize($salesRow->description),
            $amount,
            null,
            null,
            $salesRow->invoice_date?->format('n/j/Y'),
            $this->sanitizer->sanitize($salesRow->invoice_number),
            $this->sanitizer->sanitize($raw['customer_po_number'] ?? null),
            $this->sanitizer->sanitize($salesRow->bill_to_state),
            $this->sanitizer->sanitize($salesRow->customer_id),
            $this->sanitizer->sanitize($raw['notes'] ?? null),
        ], null, 'A'.$rowNumber);

        $this->applyAccountingFormat($sheet, 'D', $rowNumber);
        $sheet->getStyle('L'.$rowNumber)->getAlignment()->setWrapText(true);
    }

    private function writeAmountSubtotalPlaceholder(Worksheet $sheet, int $rowNumber, int $dataStart, int $dataEnd): void
    {
        if ($dataEnd < $dataStart) {
            $sheet->setCellValue('D'.$rowNumber, 0);
        } else {
            $sheet->setCellValue('D'.$rowNumber, '=SUM(D'.$dataStart.':D'.$dataEnd.')');
        }

        $this->applyAccountingFormat($sheet, 'D', $rowNumber);
        $sheet->getStyle('D'.$rowNumber)->getFont()->setBold(true);
    }

    /**
     * @param  list<array{row:int, family:string, start:int, end:int}>  $subtotals
     * @return list<int>
     */
    private function writeFamilySummary(Worksheet $sheet, int $startRow, array $subtotals): array
    {
        $rowsByFamily = [];
        foreach ($subtotals as $subtotal) {
            $rowsByFamily[$subtotal['family']][] = $subtotal['row'];
        }

        $familyRows = [];
        $rowNumber = $startRow;

        foreach (FeesFamilyCatalog::labels() as $label) {
            $sheet->setCellValue('C'.$rowNumber, $label);
            $familySubtotalRows = $rowsByFamily[$label] ?? [];

            if ($familySubtotalRows === []) {
                $sheet->setCellValue('D'.$rowNumber, 0);
            } else {
                $refs = array_map(fn (int $row): string => 'F'.$row, $familySubtotalRows);
                $sheet->setCellValue('D'.$rowNumber, '='.implode('+', $refs));
            }

            $this->applyAccountingFormat($sheet, 'D', $rowNumber);
            $familyRows[] = $rowNumber;
            $rowNumber++;
        }

        $first = $familyRows[0];
        $last = $familyRows[array_key_last($familyRows)];
        $sheet->setCellValue('D'.$rowNumber, '=SUM(D'.$first.':D'.$last.')');
        $this->applyAccountingFormat($sheet, 'D', $rowNumber);
        $sheet->getStyle('C'.$startRow.':D'.$rowNumber)->getFont()->setBold(true);

        return $familyRows;
    }

    /**
     * @return list<float>
     */
    private function manualAmounts(ImportBatch $importBatch, WorkbookType $workbookType): array
    {
        $marketplace = $workbookType->marketplace();

        return ImportBatchFeeAdjustment::query()
            ->where('import_batch_id', $importBatch->id)
            ->where('marketplace', $marketplace)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('amount')
            ->map(fn ($amount): float => round((float) $amount, 2))
            ->values()
            ->all();
    }

    /**
     * @param  list<float>  $amounts
     */
    private function manualAmountFormula(array $amounts): ?string
    {
        if ($amounts === []) {
            return null;
        }

        $parts = array_map(function (float $amount): string {
            $formatted = rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.');

            return $formatted === '-0' ? '0' : $formatted;
        }, $amounts);

        return '='.implode('+', $parts);
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

    private function applyPercentFormat(Worksheet $sheet, string $column, int $rowNumber): void
    {
        $sheet->getStyle($column.$rowNumber)
            ->getNumberFormat()
            ->setFormatCode(self::PERCENT_FORMAT);
    }
}
