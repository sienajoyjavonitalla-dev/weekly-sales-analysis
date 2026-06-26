<?php

namespace App\Domain\WeeklyAnalysis\Reconciliation\Data;

use App\Models\ReconciliationResult;

class ReconciliationSummary
{
    /**
     * @param  array<int, array<string, mixed>>  $reportTotals
     */
    public function __construct(
        public readonly ReconciliationResult $result,
        public readonly array $reportTotals,
    ) {
    }

    public function toArray(): array
    {
        return [
            'reconciliation' => $this->result,
            'report_totals' => $this->reportTotals,
        ];
    }
}
