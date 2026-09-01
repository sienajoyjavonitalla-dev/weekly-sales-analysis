<?php

namespace App\Domain\WeeklyAnalysis\Classification\Services;

use App\Domain\WeeklyAnalysis\Classification\Data\ClassificationMatch;
use App\Domain\WeeklyAnalysis\Classification\Support\MappingRuleCategoryResolver;
use App\Models\ImportBatch;
use App\Models\MappingRule;
use App\Models\SalesRow;
use App\Models\SalesRowStatePlacement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SalesRowClassifier
{
    public function __construct(
        private readonly MappingRuleMatcher $matcher,
        private readonly MappingRuleCategoryResolver $categoryResolver,
    ) {
    }

    /**
     * @return array{matched:int, unmatched:int, reconcilable_unmatched:int, total:int}
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

        $organizationalRules = $this->rulesForBuckets($rules, ['rhp', 'parts_tsd', 'raw']);
        $stateRules = $this->rulesForBuckets($rules, ['state']);

        $summary = [
            'matched' => 0,
            'unmatched' => 0,
            'total' => 0,
        ];

        DB::transaction(function () use ($importBatch, $organizationalRules, $stateRules, &$summary): void {
            SalesRowStatePlacement::query()
                ->whereHas('salesRow', fn ($query) => $query->where('import_batch_id', $importBatch->id))
                ->delete();

            SalesRow::query()
                ->where('import_batch_id', $importBatch->id)
                ->orderBy('id')
                ->chunkById(500, function ($salesRows) use ($organizationalRules, $stateRules, &$summary): void {
                    foreach ($salesRows as $salesRow) {
                        $summary['total']++;

                        $match = $this->matcher->match($salesRow, $organizationalRules);

                        if ($match === null) {
                            $stateOnlyMatch = $this->matcher->match($salesRow, $stateRules);

                            if ($stateOnlyMatch !== null) {
                                $this->applyMatch($salesRow, $stateOnlyMatch);
                                $summary['matched']++;
                                continue;
                            }

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

                        $this->applyMatch($salesRow, $match);
                        $this->createStatePlacementIfNeeded($salesRow, $stateRules);
                        $summary['matched']++;
                    }
                });
        });

        $summary['reconcilable_unmatched'] = SalesRow::query()
            ->where('import_batch_id', $importBatch->id)
            ->where('classification_status', 'unmatched')
            ->whereNotNull('item_id')
            ->where('item_id', '!=', '')
            ->distinct()
            ->count('item_id');

        return $summary;
    }

    /**
     * @param  Collection<int, MappingRule>  $rules
     * @param  array<int, string>  $buckets
     */
    private function rulesForBuckets(Collection $rules, array $buckets): Collection
    {
        return $rules
            ->filter(function (MappingRule $rule) use ($buckets): bool {
                $bucket = $rule->target_bucket ?? $rule->productCategory?->sales_analysis_bucket ?? 'raw';

                return in_array($bucket, $buckets, true);
            })
            ->values();
    }

    private function applyMatch(SalesRow $salesRow, ClassificationMatch $match): void
    {
        $bucket = $match->targetBucket ?? 'raw';
        $category = $this->categoryResolver->resolve($match->productCategory, $bucket);

        $salesRow->forceFill([
            'product_category_id' => $category?->id,
            'mapping_rule_id' => $match->mappingRule->id,
            'source_bucket' => $bucket,
            'classification_status' => 'matched',
            'classified_at' => now(),
            'classification_notes' => null,
        ])->save();
    }

    /**
     * @param  Collection<int, MappingRule>  $stateRules
     */
    private function createStatePlacementIfNeeded(SalesRow $salesRow, Collection $stateRules): void
    {
        if ($salesRow->source_bucket === 'state') {
            return;
        }

        $stateMatch = $this->matcher->match($salesRow, $stateRules);

        if ($stateMatch === null) {
            return;
        }

        SalesRowStatePlacement::query()->updateOrCreate(
            ['sales_row_id' => $salesRow->id],
            [
                'product_category_id' => $stateMatch->productCategory?->id,
                'mapping_rule_id' => $stateMatch->mappingRule->id,
            ],
        );
    }
}
