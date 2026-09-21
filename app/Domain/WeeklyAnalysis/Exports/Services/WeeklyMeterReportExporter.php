<?php

namespace App\Domain\WeeklyAnalysis\Exports\Services;

use App\Domain\WeeklyAnalysis\Imports\Enums\WorkbookType;
use App\Domain\WeeklyAnalysis\Reports\WeeklyMeterReportBuilder;
use App\Models\GeneratedReport;
use App\Models\ImportBatch;
use Carbon\CarbonImmutable;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WeeklyMeterReportExporter
{
    private const WEEK_FILL = '4F81BD';

    private const WEEK_FIVE_FILL = 'C459B5';

    private const TOTAL_FILL = '70AD47';

    private const TITLE_FILL = '1F4E79';

    private const ACCOUNTING_FORMAT = '#,##0.00';

    public function __construct(
        private readonly WeeklyMeterReportBuilder $builder,
        private readonly GeneratedReportRecorder $recorder,
    ) {
    }

    public function export(ImportBatch $importBatch, ?int $generatedByUserId = null): GeneratedReport
    {
        $year = (int) $importBatch->week_ending->year;
        $month = (int) $importBatch->week_ending->month;
        $report = $this->builder->build($year, $month);
        $spreadsheet = $this->makeSpreadsheet($report);

        return $this->recorder->save(
            importBatch: $importBatch,
            reportType: WorkbookType::WeeklyMeterReport->value,
            spreadsheet: $spreadsheet,
            generatedByUserId: $generatedByUserId,
            summary: [
                'sheet' => $report['title'],
                'year' => $year,
                'month' => $month,
                'weeks' => count($report['weeks']),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function makeSpreadsheet(array $report): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheetTitle = CarbonImmutable::create((int) $report['year'], (int) $report['month'], 1)->format('F Y');
        $sheet->setTitle($sheetTitle);
        $spreadsheet->getProperties()
            ->setCreator(config('app.name'))
            ->setTitle((string) $report['title']);

        $weekCount = count($report['weeks']);
        $lastColumn = $this->columnLetter(1 + ($weekCount * 2) + 2);

        $sheet->mergeCells('A1:'.$lastColumn.'1');
        $sheet->setCellValue('A1', $report['title']);
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 14],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::TITLE_FILL]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->setCellValue('A5', 'MODEL');

        foreach ($report['weeks'] as $offset => $week) {
            $unitsColumn = $this->columnLetter(2 + ($offset * 2));
            $salesColumn = $this->columnLetter(3 + ($offset * 2));
            $fill = $week['index'] === 5 ? self::WEEK_FIVE_FILL : self::WEEK_FILL;

            $sheet->mergeCells($unitsColumn.'2:'.$salesColumn.'2');
            $sheet->setCellValue($unitsColumn.'2', $week['label']);
            $sheet->mergeCells($unitsColumn.'3:'.$salesColumn.'3');
            $sheet->setCellValue($unitsColumn.'3', $week['range']);
            $sheet->setCellValue($unitsColumn.'4', '# OF');
            $sheet->setCellValue($salesColumn.'4', 'NET');
            $sheet->setCellValue($unitsColumn.'5', 'UNITS');
            $sheet->setCellValue($salesColumn.'5', 'SALES $');
            $sheet->getStyle($unitsColumn.'2:'.$salesColumn.'5')->applyFromArray($this->headerStyle($fill));
        }

        $totalUnitsColumn = $this->columnLetter(2 + ($weekCount * 2));
        $totalSalesColumn = $this->columnLetter(3 + ($weekCount * 2));
        $sheet->mergeCells($totalUnitsColumn.'2:'.$totalSalesColumn.'2');
        $sheet->setCellValue($totalUnitsColumn.'2', 'Total');
        $sheet->mergeCells($totalUnitsColumn.'3:'.$totalSalesColumn.'3');
        $sheet->setCellValue($totalUnitsColumn.'3', 'Month to Date');
        $sheet->setCellValue($totalUnitsColumn.'4', '# OF');
        $sheet->setCellValue($totalSalesColumn.'4', 'NET');
        $sheet->setCellValue($totalUnitsColumn.'5', 'UNITS');
        $sheet->setCellValue($totalSalesColumn.'5', 'SALES $');
        $sheet->getStyle($totalUnitsColumn.'2:'.$totalSalesColumn.'5')->applyFromArray($this->headerStyle(self::TOTAL_FILL));

        $rowNumber = 6;

        foreach ($report['rows'] as $modelRow) {
            $sheet->setCellValue('A'.$rowNumber, $modelRow['label']);
            $this->writeWeekValues($sheet, $rowNumber, $modelRow['weeks'], $modelRow['tracks_units']);

            if ($modelRow['tracks_units'] && $modelRow['mtd_units'] !== null) {
                $sheet->setCellValue($totalUnitsColumn.$rowNumber, $modelRow['mtd_units']);
            }

            $sheet->setCellValue($totalSalesColumn.$rowNumber, $modelRow['mtd_sales']);
            $sheet->getStyle($totalSalesColumn.$rowNumber)->getNumberFormat()->setFormatCode(self::ACCOUNTING_FORMAT);
            $sheet->getStyle($totalUnitsColumn.$rowNumber.':'.$totalSalesColumn.$rowNumber)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C6EFCE']],
            ]);
            $rowNumber++;
        }

        $sheet->setCellValue('A'.$rowNumber, 'TOTAL');
        $sheet->getStyle('A'.$rowNumber.':'.$lastColumn.$rowNumber)->getFont()->setBold(true);
        $this->writeWeekValues($sheet, $rowNumber, $report['totals']['weeks'], true);
        $sheet->setCellValue($totalUnitsColumn.$rowNumber, $report['totals']['mtd_units']);
        $sheet->setCellValue($totalSalesColumn.$rowNumber, $report['totals']['mtd_sales']);
        $sheet->getStyle($totalSalesColumn.$rowNumber)->getNumberFormat()->setFormatCode(self::ACCOUNTING_FORMAT);
        $rowNumber++;

        $sheet->setCellValue('A'.$rowNumber, '');
        foreach ($report['totals']['weeks'] as $offset => $weekValues) {
            $salesColumn = $this->columnLetter(3 + ($offset * 2));

            if ($weekValues['percent'] !== null) {
                $sheet->setCellValue($salesColumn.$rowNumber, ($weekValues['percent'] / 100));
                $sheet->getStyle($salesColumn.$rowNumber)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
            }
        }
        $sheet->setCellValue($totalSalesColumn.$rowNumber, '100%');

        $sheet->getColumnDimension('A')->setWidth(32);
        for ($column = 2; $column <= 1 + ($weekCount * 2) + 2; $column++) {
            $sheet->getColumnDimension($this->columnLetter($column))->setWidth(12);
        }

        $sheet->getStyle('A1:'.$lastColumn.($rowNumber))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'B0B0B0'],
                ],
            ],
        ]);

        return $spreadsheet;
    }

    /**
     * @param  list<array<string, mixed>>  $weeks
     */
    private function writeWeekValues(Worksheet $sheet, int $rowNumber, array $weeks, bool $tracksUnits): void
    {
        foreach ($weeks as $offset => $weekValues) {
            $unitsColumn = $this->columnLetter(2 + ($offset * 2));
            $salesColumn = $this->columnLetter(3 + ($offset * 2));

            if ($weekValues['has_data'] !== true) {
                continue;
            }

            if ($tracksUnits && array_key_exists('units', $weekValues) && $weekValues['units'] !== null) {
                $sheet->setCellValue($unitsColumn.$rowNumber, $weekValues['units']);
            }

            if (array_key_exists('sales', $weekValues) && $weekValues['sales'] !== null) {
                $sheet->setCellValue($salesColumn.$rowNumber, $weekValues['sales']);
                $sheet->getStyle($salesColumn.$rowNumber)->getNumberFormat()->setFormatCode(self::ACCOUNTING_FORMAT);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function headerStyle(string $fill): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fill]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
    }

    private function columnLetter(int $index): string
    {
        $letter = '';

        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }
}
