<?php

namespace App\Domain\WeeklyAnalysis\Reports;

use App\Models\GeneratedReport;
use App\Models\ImportBatch;
use Carbon\CarbonImmutable;

class ReportCalendarBuilder
{
    /**
     * Report types listed in the calendar popup, in display order.
     */
    private const REPORT_TYPES = ['sales_analysis', 'weekly_meter_report'];

    public function __construct(
        private readonly WeeklyMeterReportBuilder $meterReportBuilder,
    ) {
    }

    /**
     * @return array{year:int, month:int, weeks:list<array<string, mixed>>}
     */
    public function build(int $year, int $month): array
    {
        $monthStart = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $monthEnd = $monthStart->endOfMonth();

        // The calendar grid shows leading/trailing days of the neighbouring months,
        // so weeks overlapping those padding days are included as well.
        $rangeStart = $monthStart->subDays(6);
        $rangeEnd = $monthEnd->addDays(6);

        $batches = ImportBatch::query()
            ->has('uploadedFiles')
            ->with(['generatedReports', 'reconciliationResult'])
            ->whereDate('week_ending', '>=', $rangeStart->toDateString())
            ->where(function ($query) use ($rangeEnd): void {
                $query->whereNull('week_start')
                    ->orWhereDate('week_start', '<=', $rangeEnd->toDateString());
            })
            ->orderBy('week_ending')
            ->orderBy('id')
            ->get();

        $meterTotalsByMonth = [];
        $weeks = [];

        foreach ($batches as $batch) {
            $weekEnding = $batch->week_ending;
            $meterYear = (int) $weekEnding->year;
            $meterMonth = (int) $weekEnding->month;
            $meterKey = $meterYear.'-'.$meterMonth;

            $meterTotalsByMonth[$meterKey] ??= $this->meterTotalsByBatch($meterYear, $meterMonth);

            $reports = [];

            foreach (self::REPORT_TYPES as $reportType) {
                /** @var GeneratedReport|null $report */
                $report = $batch->generatedReports->firstWhere('report_type', $reportType);

                if ($report === null) {
                    continue;
                }

                $completed = $report->status === 'completed';

                $reports[] = [
                    'id' => $report->id,
                    'report_type' => $reportType,
                    'status' => $report->status,
                    'total' => $completed
                        ? $this->total($reportType, $batch, $meterTotalsByMonth[$meterKey])
                        : null,
                ];
            }

            $weeks[] = [
                'id' => $batch->id,
                'week_start' => $batch->week_start?->toDateString() ?? $weekEnding->toDateString(),
                'week_ending' => $weekEnding->toDateString(),
                'status' => $batch->status,
                'meter_year' => $meterYear,
                'meter_month' => $meterMonth,
                'reports' => $reports,
            ];
        }

        return [
            'year' => $year,
            'month' => $month,
            'weeks' => $weeks,
        ];
    }

    /**
     * @param  array<int, float|null>  $meterTotals
     */
    private function total(string $reportType, ImportBatch $batch, array $meterTotals): ?float
    {
        if ($reportType === 'sales_analysis') {
            $total = $batch->reconciliationResult?->sales_analysis_total;

            return $total === null ? null : round((float) $total, 2);
        }

        return $meterTotals[$batch->id] ?? null;
    }

    /**
     * @return array<int, float|null> Weekly meter sales total keyed by import batch id.
     */
    private function meterTotalsByBatch(int $year, int $month): array
    {
        $report = $this->meterReportBuilder->build($year, $month);
        $salesByWeekIndex = [];

        foreach ($report['totals']['weeks'] as $week) {
            $salesByWeekIndex[$week['index']] = $week['sales'];
        }

        $totals = [];

        foreach ($report['weeks'] as $week) {
            if ($week['import_batch_id'] === null) {
                continue;
            }

            $totals[(int) $week['import_batch_id']] = $salesByWeekIndex[$week['index']] ?? null;
        }

        return $totals;
    }
}
