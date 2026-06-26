<?php

namespace App\Domain\WeeklyAnalysis\Imports\Parsers;

use App\Domain\WeeklyAnalysis\Imports\Contracts\WorkbookParser;
use App\Domain\WeeklyAnalysis\Imports\Data\ParsedWorkbook;
use App\Domain\WeeklyAnalysis\Imports\Data\WorkbookValidationIssue;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

abstract class AbstractWorkbookParser implements WorkbookParser
{
    public function parse(string $path): ParsedWorkbook
    {
        try {
            $spreadsheet = IOFactory::load($path);
        } catch (Throwable $exception) {
            return new ParsedWorkbook(
                type: $this->type(),
                sheetNames: [],
                issues: [
                    new WorkbookValidationIssue('Unable to read workbook: '.$exception->getMessage()),
                ],
            );
        }

        return $this->parseSpreadsheet($spreadsheet);
    }

    abstract protected function parseSpreadsheet(Spreadsheet $spreadsheet): ParsedWorkbook;

    /**
     * @return array<int, WorkbookValidationIssue>
     */
    protected function requireSheets(Spreadsheet $spreadsheet, array $requiredSheets): array
    {
        $existingSheets = $spreadsheet->getSheetNames();
        $issues = [];

        foreach ($requiredSheets as $sheetName) {
            if (! in_array($sheetName, $existingSheets, true)) {
                $issues[] = new WorkbookValidationIssue("Missing required sheet [{$sheetName}].");
            }
        }

        return $issues;
    }

    /**
     * @return array<int, WorkbookValidationIssue>
     */
    protected function validateHeader(Worksheet $sheet, int $rowNumber, array $expectedHeaders): array
    {
        $issues = [];

        foreach ($expectedHeaders as $column => $expectedHeader) {
            $actualHeader = trim((string) $sheet->getCell($column.$rowNumber)->getCalculatedValue());

            if (strcasecmp($actualHeader, $expectedHeader) !== 0) {
                $issues[] = new WorkbookValidationIssue(
                    "Expected header [{$expectedHeader}] in column {$column}, found [{$actualHeader}].",
                    $sheet->getTitle(),
                    $rowNumber,
                );
            }
        }

        return $issues;
    }

    protected function cell(Worksheet $sheet, string $column, int $row): mixed
    {
        $value = $sheet->getCell($column.$row)->getCalculatedValue();

        return is_string($value) ? trim($value) : $value;
    }

    protected function hasAnyValue(Worksheet $sheet, int $row, array $columns): bool
    {
        foreach ($columns as $column) {
            $value = $this->cell($sheet, $column, $row);

            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }
}
