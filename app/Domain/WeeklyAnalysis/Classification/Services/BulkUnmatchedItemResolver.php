<?php

namespace App\Domain\WeeklyAnalysis\Classification\Services;

use App\Models\ImportBatch;
use App\Models\MappingRule;
use App\Models\ProductCategory;
use App\Models\SalesRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BulkUnmatchedItemResolver
{
    private const RULE_PRIORITY = 500;

    /**
     * @param  array<int, array{item_id: string, product_category_id: int}>  $resolutions
     * @return array{resolved_item_count: int, updated_row_count: int, created_or_updated_rule_count: int}
     */
    public function resolve(ImportBatch $importBatch, array $resolutions, ?int $userId): array
    {
        $expectedItemIds = SalesRow::query()
            ->where('import_batch_id', $importBatch->id)
            ->where('classification_status', 'unmatched')
            ->whereNotNull('item_id')
            ->where('item_id', '!=', '')
            ->distinct()
            ->orderBy('item_id')
            ->pluck('item_id')
            ->values()
            ->all();

        $submittedItemIds = collect($resolutions)
            ->pluck('item_id')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $expectedSorted = collect($expectedItemIds)->sort()->values()->all();

        if ($submittedItemIds !== $expectedSorted) {
            throw ValidationException::withMessages([
                'resolutions' => ['All unmatched item IDs must be resolved before saving.'],
            ]);
        }

        if (count($resolutions) !== count($expectedItemIds)) {
            throw ValidationException::withMessages([
                'resolutions' => ['Duplicate item IDs are not allowed in resolutions.'],
            ]);
        }

        $categoryIds = collect($resolutions)->pluck('product_category_id')->unique()->all();
        $categories = ProductCategory::query()
            ->whereIn('id', $categoryIds)
            ->get()
            ->keyBy('id');

        return DB::transaction(function () use ($importBatch, $resolutions, $userId, $categories): array {
            $updatedRowCount = 0;
            $ruleCount = 0;

            foreach ($resolutions as $resolution) {
                $itemId = $resolution['item_id'];
                $category = $categories->get($resolution['product_category_id']);

                if ($category === null) {
                    throw ValidationException::withMessages([
                        'resolutions' => ["Category {$resolution['product_category_id']} was not found."],
                    ]);
                }

                $sourceBucket = $category->sales_analysis_bucket ?: 'raw';
                $targetBucket = $category->sales_analysis_bucket ?: 'raw';

                $updatedRowCount += SalesRow::query()
                    ->where('import_batch_id', $importBatch->id)
                    ->where('classification_status', 'unmatched')
                    ->where('item_id', $itemId)
                    ->update([
                        'product_category_id' => $category->id,
                        'mapping_rule_id' => null,
                        'source_bucket' => $sourceBucket,
                        'classification_status' => 'manual',
                        'classified_by_user_id' => $userId,
                        'classified_at' => now(),
                        'classification_notes' => 'Resolved from upload reconcile modal.',
                    ]);

                MappingRule::query()->updateOrCreate(
                    ['name' => 'resolved → '.$itemId],
                    [
                        'product_category_id' => $category->id,
                        'source_type' => 'sales_analysis',
                        'match_field' => 'item_id',
                        'match_operator' => 'exact',
                        'pattern' => $itemId,
                        'target_bucket' => $targetBucket,
                        'priority' => self::RULE_PRIORITY,
                        'is_active' => true,
                        'metadata' => [
                            'source' => 'upload_reconcile_modal',
                            'item_id' => $itemId,
                        ],
                    ],
                );

                $ruleCount++;
            }

            return [
                'resolved_item_count' => count($resolutions),
                'updated_row_count' => $updatedRowCount,
                'created_or_updated_rule_count' => $ruleCount,
            ];
        });
    }

    /**
     * @return array<int, array{item_id: string, description: string|null, row_count: int, rows: array<int, array<string, mixed>>}>
     */
    public function itemGroups(ImportBatch $importBatch): array
    {
        $rows = SalesRow::query()
            ->where('import_batch_id', $importBatch->id)
            ->where('classification_status', 'unmatched')
            ->whereNotNull('item_id')
            ->where('item_id', '!=', '')
            ->orderBy('item_id')
            ->orderBy('source_row_number')
            ->get();

        return $rows
            ->groupBy('item_id')
            ->map(function ($groupedRows, string $itemId): array {
                $first = $groupedRows->first();

                return [
                    'item_id' => $itemId,
                    'description' => $first?->description,
                    'row_count' => $groupedRows->count(),
                    'rows' => $groupedRows->map(fn (SalesRow $row): array => [
                        'customer_id' => $row->customer_id,
                        'customer_name' => $row->customer_name,
                        'invoice_number' => $row->invoice_number,
                        'sales_rep_id' => $row->sales_rep_id,
                        'country' => $row->country,
                        'bill_to_state' => $row->bill_to_state,
                        'invoice_date' => $row->invoice_date?->toDateString(),
                        'quantity_ordered' => $row->quantity_ordered,
                        'amount' => $row->amount,
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }
}
