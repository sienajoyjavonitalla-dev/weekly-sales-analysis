<?php

namespace App\Domain\WeeklyAnalysis\Reconciliation\Services;

use App\Models\ImportBatch;
use App\Models\ProductCategory;
use App\Models\SalesRow;

class SalesAnalysisTotalsCalculator
{
    /**
     * @return array{total:float, unmatched_count:int, categories:array<int, array<string, mixed>>}
     */
    public function calculate(ImportBatch $importBatch): array
    {
        $rows = SalesRow::query()
            ->selectRaw('product_category_id, source_bucket, COUNT(*) as row_count, SUM(quantity_ordered) as quantity, SUM(amount) as amount')
            ->where('import_batch_id', $importBatch->id)
            ->whereIn('classification_status', ['matched', 'manual'])
            ->groupBy('product_category_id', 'source_bucket')
            ->get();

        $categories = ProductCategory::query()
            ->whereIn('id', $rows->pluck('product_category_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $categoryTotals = $rows->map(function ($row) use ($categories): array {
            $category = $categories->get($row->product_category_id);

            return [
                'product_category_id' => $row->product_category_id,
                'product_category_code' => $category?->code,
                'product_category_name' => $category?->name ?? 'Uncategorized',
                'source_bucket' => $row->source_bucket,
                'row_count' => (int) $row->row_count,
                'quantity' => round((float) $row->quantity, 4),
                'amount' => round((float) $row->amount, 2),
            ];
        })->values()->all();

        return [
            'total' => round(array_sum(array_column($categoryTotals, 'amount')), 2),
            'unmatched_count' => SalesRow::query()
                ->where('import_batch_id', $importBatch->id)
                ->where('classification_status', 'unmatched')
                ->count(),
            'categories' => $categoryTotals,
        ];
    }
}
