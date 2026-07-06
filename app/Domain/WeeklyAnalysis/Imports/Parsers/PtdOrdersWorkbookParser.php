<?php

namespace App\Domain\WeeklyAnalysis\Imports\Parsers;

use App\Domain\WeeklyAnalysis\Imports\Data\ParsedWorkbook;
use App\Domain\WeeklyAnalysis\Imports\Enums\WorkbookType;
use App\Domain\WeeklyAnalysis\Imports\Support\NumericValueParser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class PtdOrdersWorkbookParser extends AbstractWorkbookParser
{
    private const SHEET = 'Sheet';

    private const HEADERS = [
        'A' => 'Customer ID',
        'B' => 'Order Number',
        'C' => 'Transaction Type',
        'D' => 'Transaction Date',
        'E' => 'Total',
    ];

    public function type(): WorkbookType
    {
        return WorkbookType::PtdOrders;
    }

    protected function parseSpreadsheet(Spreadsheet $spreadsheet): ParsedWorkbook
    {
        $issues = $this->requireSheets($spreadsheet, [self::SHEET]);
        $rows = [];
        $total = 0.0;

        if ($spreadsheet->sheetNameExists(self::SHEET)) {
            $sheet = $spreadsheet->getSheetByName(self::SHEET);
            $issues = [
                ...$issues,
                ...$this->validateHeader($sheet, 1, self::HEADERS),
            ];

            for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
                if (! $this->hasAnyValue($sheet, $row, array_keys(self::HEADERS))) {
                    continue;
                }

                $amount = $this->cell($sheet, 'E', $row);
                $total += NumericValueParser::parse($amount) ?? 0.0;

                $rows[] = [
                    'source_type' => $this->type()->value,
                    'source_sheet' => self::SHEET,
                    'source_row_number' => $row,
                    'customer_id' => $this->cell($sheet, 'A', $row),
                    'order_number' => $this->cell($sheet, 'B', $row),
                    'transaction_type' => $this->cell($sheet, 'C', $row),
                    'transaction_date' => $this->cell($sheet, 'D', $row),
                    'amount' => $amount,
                ];
            }
        }

        return new ParsedWorkbook(
            type: $this->type(),
            sheetNames: $spreadsheet->getSheetNames(),
            rows: $rows,
            issues: $issues,
            metadata: [
                'order_count' => count($rows),
                'total_amount' => round($total, 2),
            ],
        );
    }
}
