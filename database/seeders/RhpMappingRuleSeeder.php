<?php

namespace Database\Seeders;

use App\Models\MappingRule;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use RuntimeException;

class RhpMappingRuleSeeder extends Seeder
{
    private const TEMPLATE_PATH = __DIR__.'/../data/rhp-sales-analysis-mapping-template.csv';

    /**
     * @var array<string, string|array{0: string, 1: string}>
     */
    private const CATEGORY_CODE_BY_NAME = [
        'RHP MISCELLANEOUS' => 'rhp_miscellaneous',
        'PACK, 5 ,RAPID RH L6, SMART SENSOR' => ['pack_5_rapid_rh_l6_smart_sensor', 'pack_5_rapid_rh_l6_smart_sensor_reader'],
        'Floor Sentry' => 'floor_sentry',
        'KIT,UPGRADE' => 'kit_upgrade',
        'KIT, RAPID RH L6 STARTER KIT PLUS-FAHRENHEIT' => 'kit_rapid_rh_l6_starter_kit_plus_fahrenheit',
        'VALUE PACK, 25PC, RHP L6 SMART SENSOR' => 'value_pack_25pc_rhp_l6_smart_sensor',
        'VALUE PACK, 50PC, RHP L6 SMART SENSOR' => 'value_pack_50pc_rhp_l6_smart_sensor',
        'SUPER VALUE PACK,RAPID RH L6, SMART SENSOR' => 'super_value_pack_rapid_rh_l6_smart_sensor',
        'WFP400+ RAPID RH L6 PROFESSIONAL FLOORING INSTALLER KIT' => 'wfp400_rapid_rh_l6_professional_flooring_installer_kit',
        'WFP450+ RRH® L6 Pro Floor Installer Kit + DG w/BT' => 'wfp450_rrh_l6_pro_floor_installer_kit_dg_w_bt',
        'Rapid RH 5.0 Complete Starter Kit Plus-Fahrenheit' => 'rapid_rh_5_0_complete_starter_kit_plus_fahrenheit',
        'TOP ASSY,ORION C555, CONCRETE ,KIT, TESTED' => 'top_assy_orion_c555_concrete_kit_tested',
        'TOP ASSY,ORION 910 KIT, TESTED' => 'top_assy_orion_910_kit_tested',
        'TOP ASSY,ORION 920 KIT, TESTED' => 'top_assy_orion_920_kit_tested',
        'TOP ASSY,ORION 930 KIT, TESTED' => 'top_assy_orion_930_kit_tested',
        'TOP ASSY,ORION 940 KIT, TESTED' => 'top_assy_orion_940_kit_tested',
        'TOP ASSY,ORION 950 KIT, TESTED' => 'top_assy_orion_950_kit_tested',
        'METER REACH EXTENDER' => 'meter_reach_extender',
        'TOP ASSY, L5300' => 'top_assy_l5300',
        'KIT, L5300+L722 STACK PROBE' => 'kit_l5300_l722_stack_probe',
    ];

    public function run(): void
    {
        if (! is_readable(self::TEMPLATE_PATH)) {
            throw new RuntimeException('RHP mapping template CSV was not found at '.self::TEMPLATE_PATH);
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
                    'target_bucket' => 'rhp',
                    'priority' => $rule['priority'],
                    'is_active' => true,
                    'metadata' => [
                        'source' => 'rhp_sales_analysis_template',
                        'category_code' => $rule['category_code'],
                        'unique_identifier' => $rule['unique_identifier'],
                    ],
                ],
            );
        }

        MappingRule::query()
            ->where('metadata->source', 'rhp_sales_analysis_template')
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
                $amount = trim($row[9] ?? '');

                if (strtolower($amount) === 'n/a' && $currentCategoryCode === 'rhp_miscellaneous') {
                    $currentCategoryCode = null;
                }

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

            if (preg_match('/^\d{3}-/', $itemId)) {
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

        $rules = [];
        $priority = 10;

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
            $code = $packCategoryOccurrence === 0
                ? 'pack_5_rapid_rh_l6_smart_sensor'
                : 'pack_5_rapid_rh_l6_smart_sensor_reader';
            $packCategoryOccurrence++;

            return $code;
        }

        $mapped = self::CATEGORY_CODE_BY_NAME[$categoryName] ?? null;

        return is_array($mapped) ? $mapped[0] : $mapped;
    }

    private function uniqueIdentifier(string $itemId): string
    {
        if (preg_match('/^(.+\d)(IN|MA)$/', $itemId, $matches)) {
            return $matches[1];
        }

        return $itemId;
    }
}
