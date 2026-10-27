<?php

namespace App\Domain\WeeklyAnalysis\Reports;

use App\Models\GeneratedReport;
use App\Models\ImportBatch;
use App\Models\ImportBatchFeeAdjustment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ReportCalendarBuilder
{
    /**
     * Report types listed in the calendar popup, in display order.
     */
    private const REPORT_TYPES = ['sales_analysis', 'weekly_meter_report', 'amazon_fees', 'walmart_fees'];

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

        $feeTotals = $this->feeTotalsByBatch();

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
                        ? $this->total($reportType, $batch, $meterTotalsByMonth[$meterKey], $feeTotals)
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
            'diagnostics' => $this->diagnostics($rangeStart, $rangeEnd, count($weeks)),
        ];
    }

    /**
     * Describes what the server can see for the requested window, including batches that are
     * left out of the calendar (for example because they have no uploaded files).
     *
     * @return array<string, mixed>
     */
    private function diagnostics(CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd, int $weeksReturned): array
    {
        $windowBatches = ImportBatch::query()
            ->withCount('uploadedFiles')
            ->with('generatedReports:id,import_batch_id,report_type,status')
            ->whereDate('week_ending', '>=', $rangeStart->toDateString())
            ->where(function ($query) use ($rangeEnd): void {
                $query->whereNull('week_start')
                    ->orWhereDate('week_start', '<=', $rangeEnd->toDateString());
            })
            ->orderBy('week_ending')
            ->orderBy('id')
            ->limit(25)
            ->get();

        $latest = ImportBatch::query()->orderByDesc('id')->first(['id', 'week_start', 'week_ending']);

        return [
            'server_time' => CarbonImmutable::now()->toIso8601String(),
            'timezone' => (string) config('app.timezone'),
            'environment' => (string) config('app.env'),
            'database' => DB::connection()->getDatabaseName(),
            'window' => [
                'from' => $rangeStart->toDateString(),
                'to' => $rangeEnd->toDateString(),
            ],
            'weeks_returned' => $weeksReturned,
            'total_batches' => ImportBatch::query()->count(),
            'latest_batch' => $latest === null ? null : [
                'id' => $latest->id,
                'week_start' => $latest->week_start?->toDateString(),
                'week_ending' => $latest->week_ending?->toDateString(),
            ],
            'batches_in_window' => $windowBatches->map(fn (ImportBatch $batch): array => [
                'id' => $batch->id,
                'week_start' => $batch->week_start?->toDateString(),
                'week_ending' => $batch->week_ending?->toDateString(),
                'status' => $batch->status,
                'uploaded_files' => (int) $batch->uploaded_files_count,
                'shown_in_calendar' => $batch->uploaded_files_count > 0,
                'reports' => $batch->generatedReports
                    ->map(fn (GeneratedReport $report): string => $report->report_type.':'.$report->status)
                    ->values()
                    ->all(),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<int, float|null>  $meterTotals
     * @param  array<int, array{amazon:float, walmart:float}>  $feeTotals
     */
    private function total(string $reportType, ImportBatch $batch, array $meterTotals, array $feeTotals): ?float
    {
        if ($reportType === 'sales_analysis') {
            $total = $batch->reconciliationResult?->sales_analysis_total;

            return $total === null ? null : round((float) $total, 2);
        }

        if ($reportType === 'amazon_fees') {
            return $feeTotals[$batch->id]['amazon'] ?? 0.0;
        }

        if ($reportType === 'walmart_fees') {
            return $feeTotals[$batch->id]['walmart'] ?? 0.0;
        }

        return $meterTotals[$batch->id] ?? null;
    }

    /**
     * @return array<int, array{amazon:float, walmart:float}>
     */
    private function feeTotalsByBatch(): array
    {
        $rows = ImportBatchFeeAdjustment::query()
            ->selectRaw('import_batch_id, marketplace, SUM(amount) as amount')
            ->groupBy('import_batch_id', 'marketplace')
            ->get();

        $totals = [];

        foreach ($rows as $row) {
            $batchId = (int) $row->import_batch_id;
            $totals[$batchId] ??= ['amazon' => 0.0, 'walmart' => 0.0];

            if ($row->marketplace === 'amazon' || $row->marketplace === 'walmart') {
                $totals[$batchId][$row->marketplace] = round((float) $row->amount, 2);
            }
        }

        return $totals;
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
