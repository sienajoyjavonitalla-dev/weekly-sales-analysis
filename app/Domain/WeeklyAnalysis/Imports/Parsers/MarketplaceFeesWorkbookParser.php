<?php

namespace App\Domain\WeeklyAnalysis\Imports\Parsers;

use App\Domain\WeeklyAnalysis\Imports\Data\ParsedWorkbook;
use App\Domain\WeeklyAnalysis\Imports\Data\WorkbookValidationIssue;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class MarketplaceFeesWorkbookParser extends AbstractWorkbookParser
{
    private const RAW_SHEET = 'Sheet';

    /**
     * Raw dumps leave columns E and F empty, with Invoice Date in G.
     *
     * @var array<string, string>
     */
    private const SPACED_HEADERS = [
        'A' => 'Sales Rep 1 ID',
        'B' => 'Item ID',
        'C' => 'Description',
        'D' => 'Amount',
        'G' => 'Invoice Date',
        'H' => 'Invoice Number',
        'I' => 'Customer PO Number',
        'J' => 'Ship to State',
        'K' => 'Customer ID',
        'L' => 'Notes',
    ];

    /**
     * Some weekly files omit the empty E/F spacer columns.
     *
     * @var array<string, string>
     */
    private const COMPACT_HEADERS = [
        'A' => 'Sales Rep 1 ID',
        'B' => 'Item ID',
        'C' => 'Description',
        'D' => 'Amount',
        'E' => 'Invoice Date',
        'F' => 'Invoice Number',
        'G' => 'Customer PO Number',
        'H' => 'Ship to State',
        'I' => 'Customer ID',
        'J' => 'Notes',
    ];

    /**
     * @var array<string, string>
     */
    private const SPACED_COLUMNS = [
        'sales_rep_id' => 'A',
        'item_id' => 'B',
        'description' => 'C',
        'amount' => 'D',
        'invoice_date' => 'G',
        'invoice_number' => 'H',
        'customer_po_number' => 'I',
        'bill_to_state' => 'J',
        'customer_id' => 'K',
        'notes' => 'L',
    ];

    /**
     * @var array<string, string>
     */
    private const COMPACT_COLUMNS = [
        'sales_rep_id' => 'A',
        'item_id' => 'B',
        'description' => 'C',
        'amount' => 'D',
        'invoice_date' => 'E',
        'invoice_number' => 'F',
        'customer_po_number' => 'G',
        'bill_to_state' => 'H',
        'customer_id' => 'I',
        'notes' => 'J',
    ];

    protected function parseSpreadsheet(Spreadsheet $spreadsheet): ParsedWorkbook
    {
        $rows = [];
        $sheet = $spreadsheet->sheetNameExists(self::RAW_SHEET)
            ? $spreadsheet->getSheetByName(self::RAW_SHEET)
            : $spreadsheet->getSheet(0);

        $layout = $this->detectLayout($sheet);
        $headers = $layout === 'compact' ? self::COMPACT_HEADERS : self::SPACED_HEADERS;
        $columns = $layout === 'compact' ? self::COMPACT_COLUMNS : self::SPACED_COLUMNS;
        $issues = $this->validateHeader($sheet, 1, $headers);

        for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
            $itemId = $this->cell($sheet, $columns['item_id'], $row);

            if ($itemId === null || $itemId === '') {
                continue;
            }

            if (! $this->hasAnyValue($sheet, $row, array_values($columns))) {
                continue;
            }

            $rows[] = [
                'source_sheet' => $sheet->getTitle(),
                'source_row_number' => $row,
                'source_bucket' => 'raw',
                'sales_rep_id' => $this->cell($sheet, $columns['sales_rep_id'], $row),
                'item_id' => $itemId,
                'description' => $this->cell($sheet, $columns['description'], $row),
                'amount' => $this->cell($sheet, $columns['amount'], $row),
                'invoice_date' => $this->cell($sheet, $columns['invoice_date'], $row),
                'invoice_number' => $this->cell($sheet, $columns['invoice_number'], $row),
                'customer_po_number' => $this->cell($sheet, $columns['customer_po_number'], $row),
                'bill_to_state' => $this->cell($sheet, $columns['bill_to_state'], $row),
                'customer_id' => $this->cell($sheet, $columns['customer_id'], $row),
                'notes' => $this->cell($sheet, $columns['notes'], $row),
                'quantity_ordered' => 0,
            ];
        }

        if ($rows === [] && $issues === []) {
            $issues[] = new WorkbookValidationIssue('No fee rows with an Item ID were found.');
        }

        return new ParsedWorkbook(
            type: $this->type(),
            sheetNames: $spreadsheet->getSheetNames(),
            rows: $rows,
            issues: $issues,
            metadata: [
                'raw_sheet' => $sheet->getTitle(),
                'layout' => $layout,
                'raw_row_count' => count($rows),
            ],
        );
    }

    private function detectLayout(Worksheet $sheet): string
    {
        $columnE = trim((string) $this->cell($sheet, 'E', 1));
        $columnG = trim((string) $this->cell($sheet, 'G', 1));

        if (strcasecmp($columnE, 'Invoice Date') === 0 || strcasecmp($columnG, 'Customer PO Number') === 0) {
            return 'compact';
        }

        return 'spaced';
    }
}
