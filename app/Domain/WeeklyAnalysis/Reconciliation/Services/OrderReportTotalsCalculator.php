<?php

namespace App\Domain\WeeklyAnalysis\Reconciliation\Services;

use App\Domain\WeeklyAnalysis\Imports\Enums\WorkbookType;
use App\Models\ImportBatch;
use App\Models\OrderRow;
use Carbon\CarbonInterface;

class OrderReportTotalsCalculator
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function calculate(ImportBatch $importBatch): array
    {
        $weekEnding = $importBatch->week_ending;

        return [
            $this->metric(
                $importBatch,
                'ptd_orders',
                'PTD Orders',
                $this->sumOrders($importBatch, WorkbookType::PtdOrders->value)
            ),
            $this->metric(
                $importBatch,
                'open_orders_current_month',
                'Open Orders for this month',
                $this->sumOpenOrdersForMonth($importBatch, $weekEnding)
            ),
            $this->metric(
                $importBatch,
                'open_orders_next_month',
                'Open Orders for next month',
                $this->sumOpenOrdersForMonth($importBatch, $weekEnding->copy()->addMonthNoOverflow())
            ),
            $this->metric(
                $importBatch,
                'open_orders_future',
                'Open Orders beyond next month',
                $this->sumFutureOpenOrders($importBatch, $weekEnding->copy()->addMonthNoOverflow())
            ),
        ];
    }

    private function sumOrders(ImportBatch $importBatch, string $sourceType): float
    {
        return round((float) OrderRow::query()
            ->where('import_batch_id', $importBatch->id)
            ->where('source_type', $sourceType)
            ->sum('amount'), 2);
    }

    private function sumOpenOrdersForMonth(ImportBatch $importBatch, CarbonInterface $month): float
    {
        return round((float) OrderRow::query()
            ->where('import_batch_id', $importBatch->id)
            ->where('source_type', WorkbookType::OpenOrders->value)
            ->whereYear('transaction_date', $month->year)
            ->whereMonth('transaction_date', $month->month)
            ->sum('amount'), 2);
    }

    private function sumFutureOpenOrders(ImportBatch $importBatch, CarbonInterface $afterMonth): float
    {
        return round((float) OrderRow::query()
            ->where('import_batch_id', $importBatch->id)
            ->where('source_type', WorkbookType::OpenOrders->value)
            ->whereDate('transaction_date', '>', $afterMonth->copy()->endOfMonth())
            ->sum('amount'), 2);
    }

    private function metric(ImportBatch $importBatch, string $key, string $label, float $amount): array
    {
        return [
            'import_batch_id' => $importBatch->id,
            'report_type' => 'total_sales_report',
            'period_label' => $importBatch->week_ending->format('F Y'),
            'metric_key' => $key,
            'quantity' => null,
            'amount' => $amount,
            'metadata' => [
                'label' => $label,
            ],
        ];
    }
}
