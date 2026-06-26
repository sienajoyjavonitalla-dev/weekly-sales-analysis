<?php

namespace App\Domain\WeeklyAnalysis\Imports\Parsers;

use App\Domain\WeeklyAnalysis\Imports\Data\ParsedWorkbook;
use App\Domain\WeeklyAnalysis\Imports\Data\WorkbookValidationIssue;
use App\Domain\WeeklyAnalysis\Imports\Enums\WorkbookType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class SalesAnalysisWorkbookParser extends AbstractWorkbookParser
{
    private const RAW_SHEET = 'Sheet';

    private const HEADERS = [
        'A' => 'Item ID',
        'B' => 'Description',
        'C' => 'Customer ID',
        'D' => 'Customer Name',
        'E' => 'Invoice Number',
        'F' => 'Sales Rep 1 ID',
        'G' => 'Country',
        'H' => 'Bill to State',
        'I' => 'Invoice Date',
        'J' => 'Qty Order Sell',
        'K' => 'Amount',
    ];

    public function type(): WorkbookType
    {
        return WorkbookType::SalesAnalysis;
    }

    protected function parseSpreadsheet(Spreadsheet $spreadsheet): ParsedWorkbook
    {
        $issues = $this->requireSheets($spreadsheet, [self::RAW_SHEET]);
        $rows = [];

        if ($spreadsheet->sheetNameExists(self::RAW_SHEET)) {
            $sheet = $spreadsheet->getSheetByName(self::RAW_SHEET);
            $issues = [
                ...$issues,
                ...$this->validateHeader($sheet, 1, self::HEADERS),
            ];

            for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
                if (! $this->hasAnyValue($sheet, $row, array_keys(self::HEADERS))) {
                    continue;
                }

                $rows[] = [
                    'source_sheet' => self::RAW_SHEET,
                    'source_row_number' => $row,
                    'source_bucket' => 'raw',
                    'item_id' => $this->cell($sheet, 'A', $row),
                    'description' => $this->cell($sheet, 'B', $row),
                    'customer_id' => $this->cell($sheet, 'C', $row),
                    'customer_name' => $this->cell($sheet, 'D', $row),
                    'invoice_number' => $this->cell($sheet, 'E', $row),
                    'sales_rep_id' => $this->cell($sheet, 'F', $row),
                    'country' => $this->cell($sheet, 'G', $row),
                    'bill_to_state' => $this->cell($sheet, 'H', $row),
                    'invoice_date' => $this->cell($sheet, 'I', $row),
                    'quantity_ordered' => $this->cell($sheet, 'J', $row),
                    'amount' => $this->cell($sheet, 'K', $row),
                ];
            }
        }

        if (! $spreadsheet->sheetNameExists('04-17-26 RHP') && ! $this->hasSheetContaining($spreadsheet, 'RHP')) {
            $issues[] = new WorkbookValidationIssue('No organized RHP sheet was found.', severity: 'warning');
        }

        if (! $spreadsheet->sheetNameExists('Parts & TSD')) {
            $issues[] = new WorkbookValidationIssue('No organized Parts & TSD sheet was found.', severity: 'warning');
        }

        return new ParsedWorkbook(
            type: $this->type(),
            sheetNames: $spreadsheet->getSheetNames(),
            rows: $rows,
            issues: $issues,
            metadata: [
                'raw_sheet' => self::RAW_SHEET,
                'raw_row_count' => count($rows),
            ],
        );
    }

    private function hasSheetContaining(Spreadsheet $spreadsheet, string $needle): bool
    {
        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            if (str_contains(strtoupper($sheetName), strtoupper($needle))) {
                return true;
            }
        }

        return false;
    }
}
