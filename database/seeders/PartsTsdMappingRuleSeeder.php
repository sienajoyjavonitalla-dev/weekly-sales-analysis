<?php

namespace Database\Seeders;

use App\Models\MappingRule;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use RuntimeException;

class PartsTsdMappingRuleSeeder extends Seeder
{
    private const TEMPLATE_PATH = __DIR__.'/../data/parts-tsd-sales-analysis-mapping-template.csv';

    /**
     * @var array<string, string|null>
     */
    private const CATEGORY_CODE_BY_NAME = [
        'Misc (on Total Sales Report)' => 'misc_on_total_sales_report',
        'TSD Parts' => 'tsd_parts',
        'TSD Repairs' => 'tsd_repairs',
    ];

    public function run(): void
    {
        if (! is_readable(self::TEMPLATE_PATH)) {
            throw new RuntimeException('Parts & TSD mapping template CSV was not found at '.self::TEMPLATE_PATH);
        }

        $categoryIds = ProductCategory::query()->pluck('id', 'code');
        $rules = $this->buildRulesFromTemplate();
        $ruleNames = [];

        foreach ($rules as $rule) {
            $ruleNames[] = $rule['name'];

            MappingRule::updateOrCreate(
                ['name' => $rule['name']],
                [
                    'product_category_id' => $rule['category_code']
                        ? ($categoryIds[$rule['category_code']] ?? null)
                        : null,
                    'source_type' => 'sales_analysis',
                    'match_field' => 'item_id',
                    'match_operator' => $rule['match_operator'],
                    'pattern' => $rule['pattern'],
                    'target_bucket' => 'parts_tsd',
                    'priority' => $rule['priority'],
                    'is_active' => true,
                    'metadata' => [
                        'source' => 'parts_tsd_sales_analysis_template',
                        'category_code' => $rule['category_code'],
                        'unique_identifier' => $rule['unique_identifier'],
                    ],
                ],
            );
        }

        MappingRule::query()
            ->where('metadata->source', 'parts_tsd_sales_analysis_template')
            ->whereNotIn('name', $ruleNames)
            ->update(['is_active' => false]);
    }

    /**
     * @return array<int, array{name: string, category_code: string|null, match_operator: string, pattern: string, priority: int, unique_identifier: string}>
     */
    private function buildRulesFromTemplate(): array
    {
        $handle = fopen(self::TEMPLATE_PATH, 'r');
        fgetcsv($handle);

        $currentCategoryCode = null;
        /** @var array<string, list<string>> $itemsByCategory */
        $itemsByCategory = [];

        while (($row = fgetcsv($handle)) !== false) {
            $itemId = trim($row[0] ?? '');

            if ($itemId === '') {
                continue;
            }

            $description = trim($row[1] ?? '');
            $customerId = trim($row[2] ?? '');

            if ($this->isProductItemId($itemId)) {
                $bucketKey = $currentCategoryCode ?? '';
                $itemsByCategory[$bucketKey][] = $itemId;

                continue;
            }

            if ($customerId !== '' || $description !== '') {
                continue;
            }

            $currentCategoryCode = self::CATEGORY_CODE_BY_NAME[$itemId] ?? null;
        }

        fclose($handle);

        return $this->buildRulesFromGroupedItems($itemsByCategory, 100);
    }

    /**
     * @param  array<string, list<string>>  $itemsByCategory
     * @return array<int, array{name: string, category_code: string|null, match_operator: string, pattern: string, priority: int, unique_identifier: string}>
     */
    private function buildRulesFromGroupedItems(array $itemsByCategory, int $startingPriority): array
    {
        $rules = [];
        $priority = $startingPriority;

        foreach ($itemsByCategory as $categoryCode => $itemIds) {
            $groupedIdentifiers = [];

            foreach (array_unique($itemIds) as $itemId) {
                $uniqueIdentifier = $this->uniqueIdentifier($itemId);
                $groupedIdentifiers[$uniqueIdentifier][] = $itemId;
            }

            foreach ($groupedIdentifiers as $uniqueIdentifier => $variants) {
                $hasMultipleVariants = count($variants) > 1;
                $usesDerivedIdentifier = $uniqueIdentifier !== $variants[0];
                $matchOperator = $hasMultipleVariants || $usesDerivedIdentifier ? 'starts_with' : 'exact';
                $pattern = $matchOperator === 'starts_with' ? $uniqueIdentifier : $variants[0];
                $label = $categoryCode === '' ? 'no category' : $categoryCode;

                $rules[] = [
                    'name' => $label.' → '.$pattern,
                    'category_code' => $categoryCode === '' ? null : $categoryCode,
                    'match_operator' => $matchOperator,
                    'pattern' => $pattern,
                    'priority' => $priority,
                    'unique_identifier' => $uniqueIdentifier,
                ];

                $priority++;
            }
        }

        return $rules;
    }

    private function isProductItemId(string $itemId): bool
    {
        if (strtolower($itemId) === 'n/a') {
            return false;
        }

        if (preg_match('/^[\d,]+(\.\d+)?$/', $itemId)) {
            return false;
        }

        if (preg_match('/^\d+$/', $itemId)) {
            return false;
        }

        return (bool) preg_match('/^\d{3}-/', $itemId)
            || str_starts_with($itemId, 'REP-')
            || str_starts_with($itemId, 'FLD-')
            || str_starts_with($itemId, 'TSD-')
            || str_starts_with($itemId, 'CST-')
            || $itemId === 'MISCELLANEOUS';
    }

    private function uniqueIdentifier(string $itemId): string
    {
        if (preg_match('/^(.+\d)(IN|MA)$/', $itemId, $matches)) {
            return $matches[1];
        }

        return $itemId;
    }
}
