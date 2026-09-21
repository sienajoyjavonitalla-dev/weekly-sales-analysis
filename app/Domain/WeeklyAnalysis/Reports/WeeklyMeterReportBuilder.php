<?php

namespace App\Domain\WeeklyAnalysis\Reports;

use App\Models\ImportBatch;
use App\Models\SalesRow;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class WeeklyMeterReportBuilder
{
    public function __construct(
        private readonly WeeklyMeterWeekCalendar $calendar,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(int $year, int $month, ?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::now();
        $weeks = $this->calendar->weeksForMonth($year, $month);
        $batches = $this->batchesForMonth($year, $month);
        $batchWeekIndex = $this->batchWeekIndex($batches, $weeks);
        $rawByLabelAndWeek = $this->rawTotalsByLabelAndWeek($batches, $batchWeekIndex);
        $weekHasData = [];

        foreach ($weeks as $week) {
            $weekHasData[$week['index']] = $batches->contains(
                fn (ImportBatch $batch): bool => ($batchWeekIndex[$batch->id] ?? null) === $week['index'],
            );
        }

        $rows = [];
        $totalWeekUnits = array_fill_keys(array_column($weeks, 'index'), 0.0);
        $totalWeekSales = array_fill_keys(array_column($weeks, 'index'), 0.0);
        $mtdUnits = 0.0;
        $mtdSales = 0.0;

        foreach (WeeklyMeterModelCatalog::rows() as $model) {
            $row = $this->buildRow(
                label: $model['label'],
                tracksUnits: $model['tracks_units'],
                weeks: $weeks,
                weekHasData: $weekHasData,
                rawByWeek: $rawByLabelAndWeek[$model['label']] ?? [],
            );

            $rows[] = $row;

            foreach ($row['weeks'] as $weekValues) {
                $index = (int) $weekValues['index'];

                if ($weekValues['has_data'] !== true) {
                    continue;
                }

                if ($model['tracks_units']) {
                    $totalWeekUnits[$index] += (float) ($weekValues['units'] ?? 0);
                }

                $totalWeekSales[$index] += (float) ($weekValues['sales'] ?? 0);
            }

            if ($model['tracks_units']) {
                $mtdUnits += (float) $row['mtd_units'];
            }

            $mtdSales += (float) $row['mtd_sales'];
        }

        $totalWeeks = [];

        foreach ($weeks as $week) {
            $index = (int) $week['index'];
            $hasData = $weekHasData[$index];
            $sales = $hasData ? round($totalWeekSales[$index], 2) : null;
            $percent = ($hasData && $mtdSales > 0)
                ? round(($totalWeekSales[$index] / $mtdSales) * 100, 1)
                : null;

            $totalWeeks[] = [
                'index' => $index,
                'units' => $hasData ? round($totalWeekUnits[$index], 4) : null,
                'sales' => $sales,
                'percent' => $percent,
                'has_data' => $hasData,
            ];
        }

        return [
            'year' => $year,
            'month' => $month,
            'title' => CarbonImmutable::create($year, $month, 1)->format('F Y').' Handmeter/RHP Shipments',
            'years' => [$today->year - 1, $today->year],
            'available_months' => $this->availableMonths($year, $today),
            'weeks' => array_map(function (array $week) use ($weekHasData, $batchWeekIndex, $batches): array {
                $batch = $batches->first(
                    fn (ImportBatch $candidate): bool => ($batchWeekIndex[$candidate->id] ?? null) === $week['index'],
                );

                return [
                    'index' => $week['index'],
                    'label' => $week['label'],
                    'range' => $week['range'],
                    'start' => $week['start'],
                    'end' => $week['end'],
                    'has_data' => $weekHasData[$week['index']],
                    'import_batch_id' => $batch?->id,
                ];
            }, $weeks),
            'rows' => $rows,
            'totals' => [
                'weeks' => $totalWeeks,
                'mtd_units' => round($mtdUnits, 4),
                'mtd_sales' => round($mtdSales, 2),
                'percent' => 100,
            ],
        ];
    }

    /**
     * @param  list<array{index:int, start:string, end:string, range:string, label:string}>  $weeks
     * @param  array<int, bool>  $weekHasData
     * @param  array<int, array{quantity:float, amount:float}>  $rawByWeek
     * @return array<string, mixed>
     */
    private function buildRow(
        string $label,
        bool $tracksUnits,
        array $weeks,
        array $weekHasData,
        array $rawByWeek,
    ): array {
        $displayedWeeks = [];
        $priorUnits = 0.0;
        $priorSales = 0.0;
        $mtdUnits = 0.0;
        $mtdSales = 0.0;

        foreach ($weeks as $week) {
            $index = (int) $week['index'];
            $hasData = $weekHasData[$index] === true;
            $rawUnits = (float) ($rawByWeek[$index]['quantity'] ?? 0);
            $rawSales = (float) ($rawByWeek[$index]['amount'] ?? 0);

            if (! $hasData) {
                $displayedWeeks[] = [
                    'index' => $index,
                    'units' => null,
                    'sales' => null,
                    'raw_units' => null,
                    'raw_sales' => null,
                    'has_data' => false,
                ];

                continue;
            }

            $displayedUnits = $index === 1 ? $rawUnits : $rawUnits - $priorUnits;
            $displayedSales = $index === 1 ? $rawSales : $rawSales - $priorSales;

            $priorUnits += $displayedUnits;
            $priorSales += $displayedSales;
            $mtdUnits += $displayedUnits;
            $mtdSales += $displayedSales;

            $displayedWeeks[] = [
                'index' => $index,
                'units' => $tracksUnits ? round($displayedUnits, 4) : null,
                'sales' => round($displayedSales, 2),
                'raw_units' => round($rawUnits, 4),
                'raw_sales' => round($rawSales, 2),
                'has_data' => true,
            ];
        }

        return [
            'label' => $label,
            'tracks_units' => $tracksUnits,
            'weeks' => $displayedWeeks,
            'mtd_units' => $tracksUnits ? round($mtdUnits, 4) : null,
            'mtd_sales' => round($mtdSales, 2),
        ];
    }

    /**
     * @return Collection<int, ImportBatch>
     */
    private function batchesForMonth(int $year, int $month): Collection
    {
        $start = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $end = $start->endOfMonth();

        return ImportBatch::query()
            ->whereDate('week_ending', '>=', $start->toDateString())
            ->whereDate('week_ending', '<=', $end->toDateString())
            ->orderBy('week_ending')
            ->get();
    }

    /**
     * @param  Collection<int, ImportBatch>  $batches
     * @param  list<array{index:int, start:string, end:string}>  $weeks
     * @return array<int, int>
     */
    private function batchWeekIndex(Collection $batches, array $weeks): array
    {
        $map = [];

        foreach ($batches as $batch) {
            $index = $this->calendar->weekIndexForEndingDate(
                $weeks,
                CarbonImmutable::parse($batch->week_ending->toDateString()),
            );

            if ($index !== null) {
                $map[$batch->id] = $index;
            }
        }

        return $map;
    }

    /**
     * @param  Collection<int, ImportBatch>  $batches
     * @param  array<int, int>  $batchWeekIndex
     * @return array<string, array<int, array{quantity:float, amount:float}>>
     */
    private function rawTotalsByLabelAndWeek(Collection $batches, array $batchWeekIndex): array
    {
        if ($batches->isEmpty()) {
            return [];
        }

        $rows = SalesRow::query()
            ->selectRaw('sales_rows.import_batch_id, product_categories.weekly_meter_row_label as label, SUM(sales_rows.quantity_ordered) as quantity, SUM(sales_rows.amount) as amount')
            ->join('product_categories', 'product_categories.id', '=', 'sales_rows.product_category_id')
            ->whereIn('sales_rows.import_batch_id', $batches->pluck('id'))
            ->whereNotNull('sales_rows.product_category_id')
            ->whereNotNull('product_categories.weekly_meter_row_label')
            ->where('product_categories.weekly_meter_row_label', '!=', '')
            ->groupBy('sales_rows.import_batch_id', 'product_categories.weekly_meter_row_label')
            ->get();

        $totals = [];

        foreach ($rows as $row) {
            $weekIndex = $batchWeekIndex[$row->import_batch_id] ?? null;

            if ($weekIndex === null) {
                continue;
            }

            $label = (string) $row->label;
            $totals[$label][$weekIndex]['quantity'] = ($totals[$label][$weekIndex]['quantity'] ?? 0) + (float) $row->quantity;
            $totals[$label][$weekIndex]['amount'] = ($totals[$label][$weekIndex]['amount'] ?? 0) + (float) $row->amount;
        }

        return $totals;
    }

    /**
     * @return list<int>
     */
    private function availableMonths(int $year, CarbonImmutable $today): array
    {
        $lastMonth = $year === $today->year ? $today->month : 12;

        return range(1, $lastMonth);
    }
}
