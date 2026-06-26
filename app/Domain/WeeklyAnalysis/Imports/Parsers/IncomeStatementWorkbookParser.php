<?php

namespace App\Domain\WeeklyAnalysis\Imports\Parsers;

use App\Domain\WeeklyAnalysis\Imports\Data\ParsedWorkbook;
use App\Domain\WeeklyAnalysis\Imports\Data\WorkbookValidationIssue;
use App\Domain\WeeklyAnalysis\Imports\Enums\WorkbookType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class IncomeStatementWorkbookParser extends AbstractWorkbookParser
{
    private const SHEET = 'Sheet';

    public function type(): WorkbookType
    {
        return WorkbookType::IncomeStatement;
    }

    protected function parseSpreadsheet(Spreadsheet $spreadsheet): ParsedWorkbook
    {
        $issues = $this->requireSheets($spreadsheet, [self::SHEET]);
        $rows = [];
        $totalRevenue = null;

        if ($spreadsheet->sheetNameExists(self::SHEET)) {
            $sheet = $spreadsheet->getSheetByName(self::SHEET);

            if (strcasecmp((string) $this->cell($sheet, 'A', 5), 'ACCT') !== 0) {
                $issues[] = new WorkbookValidationIssue('Expected ACCT header in A5.', self::SHEET, 5);
            }

            if (strcasecmp((string) $this->cell($sheet, 'D', 5), 'DESCRIPTION') !== 0) {
                $issues[] = new WorkbookValidationIssue('Expected DESCRIPTION header in D5.', self::SHEET, 5);
            }

            for ($row = 8; $row <= $sheet->getHighestDataRow(); $row++) {
                $description = $this->cell($sheet, 'D', $row);

                if ($description === null || $description === '') {
                    continue;
                }

                if (strcasecmp((string) $description, 'TOTAL REVENUE') === 0) {
                    $totalRevenue = $this->cell($sheet, 'F', $row);
                }

                $rows[] = [
                    'source_sheet' => self::SHEET,
                    'source_row_number' => $row,
                    'account_number' => $this->cell($sheet, 'A', $row),
                    'description' => $description,
                    'current_period_amount' => $this->cell($sheet, 'F', $row),
                    'current_period_percent' => $this->cell($sheet, 'G', $row),
                    'year_to_date_amount' => $this->cell($sheet, 'I', $row),
                    'year_to_date_percent' => $this->cell($sheet, 'J', $row),
                ];
            }

            if ($totalRevenue === null) {
                $issues[] = new WorkbookValidationIssue('TOTAL REVENUE row was not found.', self::SHEET);
            }
        }

        return new ParsedWorkbook(
            type: $this->type(),
            sheetNames: $spreadsheet->getSheetNames(),
            rows: $rows,
            issues: $issues,
            metadata: [
                'total_revenue' => $totalRevenue,
                'line_count' => count($rows),
            ],
        );
    }
}
