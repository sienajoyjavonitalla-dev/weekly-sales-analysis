<?php

namespace App\Domain\WeeklyAnalysis\Imports\Parsers;

use App\Domain\WeeklyAnalysis\Imports\Data\ParsedWorkbook;
use App\Domain\WeeklyAnalysis\Imports\Data\WorkbookValidationIssue;
use App\Domain\WeeklyAnalysis\Imports\Enums\WorkbookType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class TotalSalesReportWorkbookParser extends AbstractWorkbookParser
{
    public function type(): WorkbookType
    {
        return WorkbookType::TotalSalesReport;
    }

    protected function parseSpreadsheet(Spreadsheet $spreadsheet): ParsedWorkbook
    {
        $issues = [];
        $rows = [];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $sheetName = $sheet->getTitle();

            if (! str_contains((string) $this->cell($sheet, 'A', 1), 'Total Shipments')) {
                $issues[] = new WorkbookValidationIssue('Expected Total Shipments title in A1.', $sheetName, 1);
            }

            if (strcasecmp((string) $this->cell($sheet, 'A', 5), 'MODEL') !== 0) {
                $issues[] = new WorkbookValidationIssue('Expected MODEL label in A5.', $sheetName, 5);
            }

            for ($row = 6; $row <= min($sheet->getHighestDataRow(), 24); $row++) {
                $label = $this->cell($sheet, 'A', $row);

                if ($label === null || $label === '') {
                    continue;
                }

                $rows[] = [
                    'source_sheet' => $sheetName,
                    'source_row_number' => $row,
                    'row_label' => $label,
                    'week_1_units' => $this->cell($sheet, 'B', $row),
                    'week_1_amount' => $this->cell($sheet, 'C', $row),
                    'week_2_units' => $this->cell($sheet, 'D', $row),
                    'week_2_amount' => $this->cell($sheet, 'E', $row),
                    'week_3_units' => $this->cell($sheet, 'F', $row),
                    'week_3_amount' => $this->cell($sheet, 'G', $row),
                    'month_units' => $this->cell($sheet, 'L', $row),
                    'month_amount' => $this->cell($sheet, 'M', $row),
                ];
            }
        }

        if (count($spreadsheet->getSheetNames()) < 12) {
            $issues[] = new WorkbookValidationIssue('Expected a full year of monthly sheets.', severity: 'warning');
        }

        return new ParsedWorkbook(
            type: $this->type(),
            sheetNames: $spreadsheet->getSheetNames(),
            rows: $rows,
            issues: $issues,
            metadata: [
                'sheet_count' => count($spreadsheet->getSheetNames()),
                'template_row_count' => count($rows),
            ],
        );
    }
}
