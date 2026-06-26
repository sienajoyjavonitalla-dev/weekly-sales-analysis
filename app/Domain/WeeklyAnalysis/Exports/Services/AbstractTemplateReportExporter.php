<?php

namespace App\Domain\WeeklyAnalysis\Exports\Services;

use App\Models\ImportBatch;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class AbstractTemplateReportExporter
{
    protected function currentMonthSheet(Spreadsheet $spreadsheet, ImportBatch $importBatch): ?Worksheet
    {
        $expected = $importBatch->week_ending->format('F Y');

        return $spreadsheet->getSheetByName($expected);
    }

    /**
     * @return array{units:string, amount:string}
     */
    protected function weekColumns(Worksheet $sheet, ImportBatch $importBatch): array
    {
        $weekEnding = $importBatch->week_ending;

        foreach (['B', 'D', 'F', 'H', 'J'] as $unitsColumn) {
            $range = (string) $sheet->getCell($unitsColumn.'3')->getCalculatedValue();

            if ($range === '') {
                continue;
            }

            if ($this->rangeContainsDate($range, $weekEnding->month, $weekEnding->day)) {
                return [
                    'units' => $unitsColumn,
                    'amount' => chr(ord($unitsColumn) + 1),
                ];
            }
        }

        return ['units' => 'F', 'amount' => 'G'];
    }

    protected function findLabelRow(Worksheet $sheet, string $label, int $startRow, int $endRow): ?int
    {
        for ($row = $startRow; $row <= $endRow; $row++) {
            $actual = trim((string) $sheet->getCell('A'.$row)->getCalculatedValue());

            if (strcasecmp($actual, $label) === 0) {
                return $row;
            }
        }

        return null;
    }

    private function rangeContainsDate(string $range, int $month, int $day): bool
    {
        if (! preg_match('/(\d{2})\/(\d{2})-(\d{2})\/(\d{2})/', $range, $matches)) {
            return false;
        }

        $startMonth = (int) $matches[1];
        $startDay = (int) $matches[2];
        $endMonth = (int) $matches[3];
        $endDay = (int) $matches[4];

        return $month >= $startMonth
            && $month <= $endMonth
            && $day >= $startDay
            && $day <= $endDay;
    }
}
