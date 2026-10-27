<?php

namespace App\Domain\WeeklyAnalysis\Reports;

use App\Models\GeneratedReport;
use App\Models\ImportBatch;
use App\Models\ImportBatchFeeAdjustment;
use App\Models\ReconciliationResult;
use App\Models\SalesRow;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Aggregates Sales Analysis and Weekly Meter Report statistics for one month.
 *
 * Sales Analysis workbooks are month-to-date, so each batch holds the cumulative total for the month
 * up to its week ending. Weekly figures are derived by subtracting the previous batch's total, which
 * mirrors how the Weekly Meter Report derives its weekly columns.
 */
class DashboardStatsBuilder
{
    private const TOP_LIMIT = 10;

    private const MATCHED_STATUSES = ['matched', 'manual'];

    private const BUCKET_LABELS = [
        'rhp' => 'RHP',
        'parts_tsd' => 'PARTS & TSD',
        'state' => 'STATE',
    ];

    public function __construct(
        private readonly WeeklyMeterReportBuilder $meterReportBuilder,
        private readonly WeeklyMeterWeekCalendar $calendar,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(int $year, int $month): array
    {
        $monthStart = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $previousMonth = $monthStart->subMonth();

        $calendarWeeks = $this->calendar->weeksForMonth($year, $month);
        $batches = $this->batchesForMonth($monthStart);
        $batchWeekIndex = $this->batchWeekIndex($batches, $calendarWeeks);
        $batchesById = $batches->keyBy('id');

        $amountsByBatch = $this->amountsByBatchAndBucket($batches->pluck('id')->all());
        $reconciliations = ReconciliationResult::query()
            ->whereIn('import_batch_id', $batches->pluck('id'))
            ->get()
            ->keyBy('import_batch_id');
        $unmatchedByBatch = $this->unmatchedCountsByBatch($batches->pluck('id')->all());
        $feeTotalsByBatch = $this->feeTotalsByBatch($batches->pluck('id')->all());

        $meter = $this->meterReportBuilder->build($year, $month);
        $meterWeeks = collect($meter['totals']['weeks'])->keyBy('index');

        // One batch per week index (the latest upload wins), ordered chronologically.
        $latestBatchByIndex = [];

        foreach ($batches as $batch) {
            $index = $batchWeekIndex[$batch->id] ?? null;

            if ($index !== null) {
                $latestBatchByIndex[$index] = $batch->id;
            }
        }

        ksort($latestBatchByIndex);

        $weeks = [];
        $priorMtd = 0.0;
        $latestBatchId = null;

        foreach ($calendarWeeks as $week) {
            $index = (int) $week['index'];
            $batchId = $latestBatchByIndex[$index] ?? null;
            $meterWeek = $meterWeeks->get($index, []);
            $mtd = null;
            $weekly = null;
            $reconciliation = null;

            if ($batchId !== null) {
                $latestBatchId = $batchId;
                $mtd = round(array_sum($amountsByBatch[$batchId] ?? []), 2);
                $weekly = round($mtd - $priorMtd, 2);
                $priorMtd = $mtd;
                $result = $reconciliations->get($batchId);

                $reconciliation = $result === null ? null : [
                    'income_statement_total' => round((float) $result->income_statement_total, 2),
                    'marketplace_fee_total' => round((float) $result->marketplace_fee_total, 2),
                    'difference' => round((float) $result->difference, 2),
                    'is_balanced' => (bool) $result->is_balanced,
                ];
            }

            $weeks[] = [
                'index' => $index,
                'label' => $week['label'],
                'range' => $week['range'],
                'has_data' => $batchId !== null,
                'import_batch_id' => $batchId,
                'sales_analysis_weekly' => $weekly,
                'sales_analysis_mtd' => $mtd,
                'meter_sales' => $meterWeek['sales'] ?? null,
                'meter_units' => $meterWeek['units'] ?? null,
                'meter_percent' => $meterWeek['percent'] ?? null,
                'unmatched_rows' => $batchId !== null ? ($unmatchedByBatch[$batchId] ?? 0) : null,
                'amazon_fees_total' => $batchId !== null ? ($feeTotalsByBatch[$batchId]['amazon'] ?? 0.0) : null,
                'walmart_fees_total' => $batchId !== null ? ($feeTotalsByBatch[$batchId]['walmart'] ?? 0.0) : null,
                'reconciliation' => $reconciliation,
            ];
        }

        $salesAnalysisMtd = $latestBatchId !== null
            ? round(array_sum($amountsByBatch[$latestBatchId] ?? []), 2)
            : 0.0;
        $previousSalesAnalysisMtd = $this->previousMonthSalesAnalysisTotal($previousMonth);
        $previousMeter = $this->meterReportBuilder->build($previousMonth->year, $previousMonth->month);

        return [
            'year' => $year,
            'month' => $month,
            'title' => $monthStart->format('F Y'),
            'weeks' => $weeks,
            'sales_analysis' => [
                'mtd_total' => $salesAnalysisMtd,
                'previous_mtd_total' => $previousSalesAnalysisMtd,
                'change_percent' => $this->changePercent($salesAnalysisMtd, $previousSalesAnalysisMtd),
                'latest_import_batch_id' => $latestBatchId,
                'buckets' => $this->buckets($amountsByBatch[$latestBatchId] ?? []),
                'top_categories' => $latestBatchId !== null ? $this->topCategories($latestBatchId) : [],
                'top_customers' => $latestBatchId !== null ? $this->topCustomers($latestBatchId) : [],
            ],
            'meter' => [
                'mtd_sales' => $meter['totals']['mtd_sales'],
                'mtd_units' => $meter['totals']['mtd_units'],
                'previous_mtd_sales' => $previousMeter['totals']['mtd_sales'],
                'previous_mtd_units' => $previousMeter['totals']['mtd_units'],
                'sales_change_percent' => $this->changePercent(
                    (float) $meter['totals']['mtd_sales'],
                    (float) $previousMeter['totals']['mtd_sales'],
                ),
                'units_change_percent' => $this->changePercent(
                    (float) $meter['totals']['mtd_units'],
                    (float) $previousMeter['totals']['mtd_units'],
                ),
                'additional_fees_total' => $meter['totals']['additional_fees_total'],
                'additional_fees_percent' => $meter['totals']['additional_fees_percent'],
                'top_models_by_sales' => $this->topModels($meter['rows'], 'mtd_sales'),
                'top_models_by_units' => $this->topModels($meter['rows'], 'mtd_units'),
            ],
            'health' => $this->health($batches, $calendarWeeks, $latestBatchByIndex, $reconciliations, $unmatchedByBatch),
        ];
    }

    /**
     * @return Collection<int, ImportBatch>
     */
    private function batchesForMonth(CarbonImmutable $monthStart): Collection
    {
        return ImportBatch::query()
            ->whereDate('week_ending', '>=', $monthStart->toDateString())
            ->whereDate('week_ending', '<=', $monthStart->endOfMonth()->toDateString())
            ->orderBy('week_ending')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, ImportBatch>  $batches
     * @param  list<array{index:int, start:string, end:string}>  $calendarWeeks
     * @return array<int, int>
     */
    private function batchWeekIndex(Collection $batches, array $calendarWeeks): array
    {
        $map = [];

        foreach ($batches as $batch) {
            $index = $this->calendar->weekIndexForEndingDate(
                $calendarWeeks,
                CarbonImmutable::parse($batch->week_ending->toDateString()),
            );

            if ($index !== null) {
                $map[$batch->id] = $index;
            }
        }

        return $map;
    }

    /**
     * @param  list<int>  $batchIds
     * @return array<int, array<string, float>> batch id => bucket => amount
     */
    private function amountsByBatchAndBucket(array $batchIds): array
    {
        if ($batchIds === []) {
            return [];
        }

        $rows = SalesRow::query()
            ->salesAnalysis()
            ->selectRaw('import_batch_id, source_bucket, SUM(amount) as amount')
            ->whereIn('import_batch_id', $batchIds)
            ->whereIn('classification_status', self::MATCHED_STATUSES)
            ->groupBy('import_batch_id', 'source_bucket')
            ->get();

        $amounts = [];

        foreach ($rows as $row) {
            $amounts[(int) $row->import_batch_id][(string) $row->source_bucket] = round((float) $row->amount, 2);
        }

        return $amounts;
    }

    /**
     * @param  list<int>  $batchIds
     * @return array<int, array{amazon:float, walmart:float}>
     */
    private function feeTotalsByBatch(array $batchIds): array
    {
        if ($batchIds === []) {
            return [];
        }

        $rows = ImportBatchFeeAdjustment::query()
            ->selectRaw('import_batch_id, marketplace, SUM(amount) as amount')
            ->whereIn('import_batch_id', $batchIds)
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
     * @param  list<int>  $batchIds
     * @return array<int, int>
     */
    private function unmatchedCountsByBatch(array $batchIds): array
    {
        if ($batchIds === []) {
            return [];
        }

        return SalesRow::query()
            ->salesAnalysis()
            ->selectRaw('import_batch_id, COUNT(*) as aggregate')
            ->whereIn('import_batch_id', $batchIds)
            ->where('classification_status', 'unmatched')
            ->groupBy('import_batch_id')
            ->pluck('aggregate', 'import_batch_id')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    private function previousMonthSalesAnalysisTotal(CarbonImmutable $previousMonth): float
    {
        $latest = $this->batchesForMonth($previousMonth)->last();

        if ($latest === null) {
            return 0.0;
        }

        return round((float) SalesRow::query()
            ->salesAnalysis()
            ->where('import_batch_id', $latest->id)
            ->whereIn('classification_status', self::MATCHED_STATUSES)
            ->sum('amount'), 2);
    }

    /**
     * @param  array<string, float>  $amounts
     * @return list<array{bucket:string, label:string, amount:float}>
     */
    private function buckets(array $amounts): array
    {
        $buckets = [];

        foreach ($amounts as $bucket => $amount) {
            if ($amount == 0.0) {
                continue;
            }

            $buckets[] = [
                'bucket' => $bucket,
                'label' => self::BUCKET_LABELS[$bucket] ?? strtoupper(str_replace('_', ' ', $bucket)),
                'amount' => $amount,
            ];
        }

        usort($buckets, fn (array $a, array $b): int => $b['amount'] <=> $a['amount']);

        return $buckets;
    }

    /**
     * @return list<array{name:string, amount:float, quantity:float}>
     */
    private function topCategories(int $batchId): array
    {
        return SalesRow::query()
            ->salesAnalysis()
            ->selectRaw("COALESCE(product_categories.name, 'Uncategorized') as name, SUM(sales_rows.amount) as amount, SUM(sales_rows.quantity_ordered) as quantity")
            ->leftJoin('product_categories', 'product_categories.id', '=', 'sales_rows.product_category_id')
            ->where('sales_rows.import_batch_id', $batchId)
            ->whereIn('sales_rows.classification_status', self::MATCHED_STATUSES)
            ->groupBy('product_categories.id', 'product_categories.name')
            ->orderByDesc('amount')
            ->limit(self::TOP_LIMIT)
            ->get()
            ->map(fn ($row): array => [
                'name' => (string) $row->name,
                'amount' => round((float) $row->amount, 2),
                'quantity' => round((float) $row->quantity, 4),
            ])
            ->all();
    }

    /**
     * @return list<array{name:string, amount:float, rows:int}>
     */
    private function topCustomers(int $batchId): array
    {
        return SalesRow::query()
            ->salesAnalysis()
            ->selectRaw("COALESCE(NULLIF(customer_name, ''), NULLIF(customer_id, ''), 'Unknown') as customer, SUM(amount) as amount, COUNT(*) as row_count")
            ->where('import_batch_id', $batchId)
            ->whereIn('classification_status', self::MATCHED_STATUSES)
            ->groupBy('customer')
            ->orderByDesc('amount')
            ->limit(self::TOP_LIMIT)
            ->get()
            ->map(fn ($row): array => [
                'name' => (string) $row->customer,
                'amount' => round((float) $row->amount, 2),
                'rows' => (int) $row->row_count,
            ])
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{label:string, sales:float, units:float|null}>
     */
    private function topModels(array $rows, string $sortKey): array
    {
        $models = array_values(array_filter(
            $rows,
            fn (array $row): bool => (float) ($row[$sortKey] ?? 0) > 0,
        ));

        usort($models, fn (array $a, array $b): int => (float) $b[$sortKey] <=> (float) $a[$sortKey]);

        return array_map(fn (array $row): array => [
            'label' => $row['label'],
            'sales' => (float) $row['mtd_sales'],
            'units' => $row['mtd_units'] === null ? null : (float) $row['mtd_units'],
        ], array_slice($models, 0, self::TOP_LIMIT));
    }

    /**
     * @param  Collection<int, ImportBatch>  $batches
     * @param  list<array<string, mixed>>  $calendarWeeks
     * @param  array<int, int>  $latestBatchByIndex
     * @param  Collection<int, ReconciliationResult>  $reconciliations
     * @param  array<int, int>  $unmatchedByBatch
     * @return array<string, mixed>
     */
    private function health(
        Collection $batches,
        array $calendarWeeks,
        array $latestBatchByIndex,
        Collection $reconciliations,
        array $unmatchedByBatch,
    ): array {
        $reconciledWeeks = 0;
        $balancedWeeks = 0;

        foreach ($latestBatchByIndex as $batchId) {
            $result = $reconciliations->get($batchId);

            if ($result === null) {
                continue;
            }

            $reconciledWeeks++;

            if ($result->is_balanced) {
                $balancedWeeks++;
            }
        }

        $reports = $batches->isEmpty()
            ? []
            : GeneratedReport::query()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->whereIn('import_batch_id', $batches->pluck('id'))
                ->groupBy('status')
                ->pluck('aggregate', 'status')
                ->map(fn ($count): int => (int) $count)
                ->all();

        return [
            'weeks_in_month' => count($calendarWeeks),
            'weeks_with_data' => count($latestBatchByIndex),
            'unmatched_rows' => array_sum($unmatchedByBatch),
            'reconciled_weeks' => $reconciledWeeks,
            'balanced_weeks' => $balancedWeeks,
            'unbalanced_weeks' => $reconciledWeeks - $balancedWeeks,
            'reports_by_status' => $reports,
        ];
    }

    private function changePercent(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
