<?php

namespace App\Domain\WeeklyAnalysis\Reconciliation\Services;

use App\Domain\WeeklyAnalysis\Reconciliation\Data\ReconciliationSummary;
use App\Models\ImportBatch;
use App\Models\IncomeStatementLine;
use App\Models\MarketplaceFee;
use App\Models\ReconciliationResult;
use App\Models\ReportTotal;
use Illuminate\Support\Facades\DB;

class WeeklyReconciliationService
{
    public function __construct(
        private readonly SalesAnalysisTotalsCalculator $salesTotalsCalculator,
        private readonly OrderReportTotalsCalculator $orderReportTotalsCalculator,
    ) {
    }

    public function reconcile(ImportBatch $importBatch, float $tolerance = 0.01): ReconciliationSummary
    {
        return DB::transaction(function () use ($importBatch, $tolerance): ReconciliationSummary {
            $salesTotals = $this->salesTotalsCalculator->calculate($importBatch);
            $incomeStatementTotal = $this->incomeStatementTotal($importBatch);
            $marketplaceFeeTotal = $this->marketplaceFeeTotal($importBatch);
            $adjustedIncomeStatementTotal = round($incomeStatementTotal + $marketplaceFeeTotal, 2);
            $difference = round($adjustedIncomeStatementTotal - $salesTotals['total'], 2);
            $reportTotals = $this->persistReportTotals($importBatch);

            $result = ReconciliationResult::query()->updateOrCreate(
                ['import_batch_id' => $importBatch->id],
                [
                    'sales_analysis_total' => $salesTotals['total'],
                    'income_statement_total' => $incomeStatementTotal,
                    'marketplace_fee_total' => $marketplaceFeeTotal,
                    'adjusted_income_statement_total' => $adjustedIncomeStatementTotal,
                    'difference' => $difference,
                    'is_balanced' => abs($difference) <= $tolerance,
                    'tolerance' => $tolerance,
                    'category_totals' => $salesTotals['categories'],
                    'messages' => $this->messages($salesTotals['unmatched_count'], $incomeStatementTotal),
                ],
            );

            return new ReconciliationSummary(
                result: $result->refresh(),
                reportTotals: $reportTotals,
            );
        });
    }

    private function incomeStatementTotal(ImportBatch $importBatch): float
    {
        $totalRevenue = IncomeStatementLine::query()
            ->where('import_batch_id', $importBatch->id)
            ->whereRaw('LOWER(description) = ?', ['total revenue'])
            ->value('current_period_amount');

        if ($totalRevenue !== null) {
            return round((float) $totalRevenue, 2);
        }

        return round((float) IncomeStatementLine::query()
            ->where('import_batch_id', $importBatch->id)
            ->where('source_row_number', '>=', 8)
            ->where('source_row_number', '<', 20)
            ->sum('current_period_amount'), 2);
    }

    private function marketplaceFeeTotal(ImportBatch $importBatch): float
    {
        return round((float) MarketplaceFee::query()
            ->where('import_batch_id', $importBatch->id)
            ->sum('amount'), 2);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function persistReportTotals(ImportBatch $importBatch): array
    {
        $totals = $this->orderReportTotalsCalculator->calculate($importBatch);

        foreach ($totals as $total) {
            ReportTotal::query()->updateOrCreate(
                [
                    'import_batch_id' => $total['import_batch_id'],
                    'report_type' => $total['report_type'],
                    'metric_key' => $total['metric_key'],
                ],
                [
                    'period_label' => $total['period_label'],
                    'quantity' => $total['quantity'],
                    'amount' => $total['amount'],
                    'metadata' => $total['metadata'],
                ],
            );
        }

        return $totals;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function messages(int $unmatchedCount, float $incomeStatementTotal): array
    {
        $messages = [];

        if ($unmatchedCount > 0) {
            $messages[] = [
                'severity' => 'warning',
                'message' => "{$unmatchedCount} sales rows are still unmatched and excluded from reconciliation totals.",
            ];
        }

        if ($incomeStatementTotal === 0.0) {
            $messages[] = [
                'severity' => 'warning',
                'message' => 'Income Statement total revenue is zero or missing.',
            ];
        }

        return $messages;
    }
}
