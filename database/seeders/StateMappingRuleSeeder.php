<?php

namespace Database\Seeders;

use App\Models\MappingRule;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use RuntimeException;

class StateMappingRuleSeeder extends Seeder
{
    private const TEMPLATE_PATH = __DIR__.'/../data/state-sales-analysis-mapping-template.csv';

    /**
     * State organized workbook category headers map to state-bucket category codes.
     * The current template is flat sales data, so most rules remain uncategorized.
     *
     * @var array<string, string|array{0: string, 1: string}>
     */
    private const CATEGORY_CODE_BY_NAME = [
        'PACK, 5 ,RAPID RH L6, SMART SENSOR' => ['state_pack_5_rapid_rh_l6_smart_sensor', 'state_pack_5_rapid_rh_l6_smart_sensor_reader'],
        'Floor Sentry' => 'state_floor_sentry',
        'KIT,UPGRADE' => 'state_kit_upgrade',
        'KIT, RAPID RH L6 STARTER KIT PLUS-FAHRENHEIT' => 'state_kit_rapid_rh_l6_starter_kit_plus_fahrenheit',
        'VALUE PACK, 25PC, RHP L6 SMART SENSOR' => 'state_value_pack_25pc_rhp_l6_smart_sensor',
        'VALUE PACK, 50PC, RHP L6 SMART SENSOR' => 'state_value_pack_50pc_rhp_l6_smart_sensor',
        'SUPER VALUE PACK,RAPID RH L6, SMART SENSOR' => 'state_super_value_pack_rapid_rh_l6_smart_sensor',
        'Rapid RH 5.0 Complete Starter Kit Plus-Fahrenheit' => 'state_rapid_rh_5_0_complete_starter_kit_plus_fahrenheit',
    ];

    public function run(): void
    {
        if (! is_readable(self::TEMPLATE_PATH)) {
            throw new RuntimeException('State mapping template CSV was not found at '.self::TEMPLATE_PATH);
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
                    'target_bucket' => 'state',
                    'priority' => $rule['priority'],
                    'is_active' => true,
                    'metadata' => [
                        'source' => 'state_sales_analysis_template',
                        'category_code' => $rule['category_code'],
                        'unique_identifier' => $rule['unique_identifier'],
                    ],
                ],
            );
        }

        MappingRule::query()
            ->where('metadata->source', 'state_sales_analysis_template')
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
        $packCategoryOccurrence = 0;
        /** @var array<string, list<string>> $itemsByCategory */
        $itemsByCategory = [];

        while (($row = fgetcsv($handle)) !== false) {
            $itemId = trim($row[0] ?? '');

            if ($itemId === '') {
                continue;
            }

            $description = trim($row[1] ?? '');
            $customerId = trim($row[2] ?? '');

            if (strtolower($itemId) === 'n/a') {
                continue;
            }

            if (preg_match('/^\d+$/', $itemId) && $description !== '') {
                continue;
            }

            if ($this->isProductItemId($itemId)) {
                $bucketKey = $currentCategoryCode ?? '';
                $itemsByCategory[$bucketKey][] = $itemId;

                continue;
            }

            if ($customerId !== '' || $description !== '') {
                continue;
            }

            $currentCategoryCode = $this->resolveCategoryCode($itemId, $packCategoryOccurrence);
        }

        fclose($handle);

        return $this->buildRulesFromGroupedItems($itemsByCategory, 10);
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

    private function resolveCategoryCode(string $categoryName, int &$packCategoryOccurrence): ?string
    {
        if ($categoryName === 'PACK, 5 ,RAPID RH L6, SMART SENSOR') {
            $codes = self::CATEGORY_CODE_BY_NAME[$categoryName];
            $code = $packCategoryOccurrence === 0 ? $codes[0] : $codes[1];
            $packCategoryOccurrence++;

            return $code;
        }

        $mapped = self::CATEGORY_CODE_BY_NAME[$categoryName] ?? null;

        return is_array($mapped) ? $mapped[0] : $mapped;
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

        return (bool) preg_match('/^\d{3}-/', $itemId);
    }

    private function uniqueIdentifier(string $itemId): string
    {
        if (preg_match('/^(.+\d)(IN|MA)$/', $itemId, $matches)) {
            return $matches[1];
        }

        return $itemId;
    }
}
