<?php

namespace App\Domain\WeeklyAnalysis\Imports\Parsers;

use App\Domain\WeeklyAnalysis\Imports\Data\ParsedWorkbook;
use App\Domain\WeeklyAnalysis\Imports\Enums\WorkbookType;
use App\Domain\WeeklyAnalysis\Imports\Support\NumericValueParser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class OpenOrdersWorkbookParser extends AbstractWorkbookParser
{
    private const SHEET = 'Sheet';

    private const HEADERS = [
        'A' => 'Item ID',
        'C' => 'Description',
        'D' => 'Order Number',
        'E' => 'Sold-To ID',
        'F' => 'Transaction Date',
        'G' => 'Qty Ordered',
        'H' => 'Ext Price',
        'I' => 'Location ID',
    ];

    public function type(): WorkbookType
    {
        return WorkbookType::OpenOrders;
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
                if (! $this->hasAnyValue($sheet, $row, ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'])) {
                    continue;
                }

                $amount = $this->cell($sheet, 'H', $row);
                $total += NumericValueParser::parse($amount) ?? 0.0;

                $rows[] = [
                    'source_type' => $this->type()->value,
                    'source_sheet' => self::SHEET,
                    'source_row_number' => $row,
                    'item_id' => $this->cell($sheet, 'B', $row),
                    'description' => $this->cell($sheet, 'C', $row),
                    'order_number' => $this->cell($sheet, 'D', $row),
                    'sold_to_id' => $this->cell($sheet, 'E', $row),
                    'transaction_date' => $this->cell($sheet, 'F', $row),
                    'quantity_ordered' => $this->cell($sheet, 'G', $row),
                    'amount' => $amount,
                    'location_id' => $this->cell($sheet, 'I', $row),
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
