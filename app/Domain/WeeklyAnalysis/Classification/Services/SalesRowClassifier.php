<?php

namespace App\Domain\WeeklyAnalysis\Classification\Services;

use App\Models\ImportBatch;
use App\Models\MappingRule;
use App\Models\SalesRow;
use Illuminate\Support\Facades\DB;

class SalesRowClassifier
{
    public function __construct(
        private readonly MappingRuleMatcher $matcher,
    ) {
    }

    /**
     * @return array{matched:int, unmatched:int, total:int}
     */
    public function classifyBatch(ImportBatch $importBatch): array
    {
        $rules = MappingRule::query()
            ->with('productCategory')
            ->where('source_type', 'sales_analysis')
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('name')
            ->get();

        $summary = [
            'matched' => 0,
            'unmatched' => 0,
            'total' => 0,
        ];

        DB::transaction(function () use ($importBatch, $rules, &$summary): void {
            SalesRow::query()
                ->where('import_batch_id', $importBatch->id)
                ->orderBy('id')
                ->chunkById(500, function ($salesRows) use ($rules, &$summary): void {
                    foreach ($salesRows as $salesRow) {
                        $summary['total']++;
                        $match = $this->matcher->match($salesRow, $rules);

                        if ($match === null) {
                            $salesRow->forceFill([
                                'product_category_id' => null,
                                'mapping_rule_id' => null,
                                'source_bucket' => 'raw',
                                'classification_status' => 'unmatched',
                                'classified_at' => null,
                                'classification_notes' => null,
                            ])->save();

                            $summary['unmatched']++;
                            continue;
                        }

                        $salesRow->forceFill([
                            'product_category_id' => $match->productCategory?->id,
                            'mapping_rule_id' => $match->mappingRule->id,
                            'source_bucket' => $match->targetBucket ?? 'raw',
                            'classification_status' => 'matched',
                            'classified_at' => now(),
                            'classification_notes' => null,
                        ])->save();

                        $summary['matched']++;
                    }
                });
        });

        return $summary;
    }
}
