<?php

namespace Database\Seeders;

use App\Models\MappingRule;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use RuntimeException;

class PartsTsdMappingRuleSeeder extends Seeder
{
    private const LEGACY_TEMPLATE_PATH = __DIR__.'/../data/parts-tsd-legacy-sales-analysis-mapping-template.csv';

    private const ORGANIZED_TEMPLATE_PATH = __DIR__.'/../data/parts-tsd-sales-analysis-mapping-template.csv';

    private const LEGACY_METADATA_SOURCE = 'parts_tsd_legacy_sales_analysis_template';

    private const ORGANIZED_METADATA_SOURCE = 'parts_tsd_sales_analysis_template';

    /**
     * The organized workbook begins with an RHP-style layout used for export reference only.
     * Mapping rules for that section are owned by RhpMappingRuleSeeder.
     */
    private const ORGANIZED_SECTION_START_HEADER = 'TRUE REMOTE';

    /**
     * @var array<string, string>
     */
    private const LEGACY_CATEGORY_CODE_BY_NAME = [
        'Misc (on Total Sales Report)' => 'misc_on_total_sales_report',
        'TSD Parts' => 'tsd_parts',
        'TSD Repairs' => 'tsd_repairs',
    ];

    /**
     * @var array<string, string|array{0: string, 1: string}>
     */
    private const ORGANIZED_CATEGORY_CODE_BY_NAME = [
        'RHP MISCELLANEOUS' => 'parts_tsd_rhp_miscellaneous',
        'PACK, 5 ,RAPID RH L6, SMART SENSOR' => ['parts_tsd_pack_5_rapid_rh_l6_smart_sensor', 'parts_tsd_pack_5_rapid_rh_l6_smart_sensor_reader'],
        'TRUE REMOTE' => 'parts_tsd_true_remote',
        'KIT,UPGRADE' => 'parts_tsd_kit_upgrade',
        'Floor Sentry' => 'parts_tsd_floor_sentry',
        'KIT, RAPID RH L6 STARTER KIT PLUS-FAHRENHEIT' => 'parts_tsd_kit_rapid_rh_l6_starter_kit_plus_fahrenheit',
        'VALUE PACK, 25PC, RHP L6 SMART SENSOR' => 'parts_tsd_value_pack_25pc_rhp_l6_smart_sensor',
        'VALUE PACK, 50PC, RHP L6 SMART SENSOR' => 'parts_tsd_value_pack_50pc_rhp_l6_smart_sensor',
        'SUPER VALUE PACK,RAPID RH L6, SMART SENSOR' => 'parts_tsd_super_value_pack_rapid_rh_l6_smart_sensor',
        'WFP350+ Rapid RH® L6 Adv. Concrete Kit + DataGrabbers w/BT' => 'parts_tsd_wfp350_rapid_rh_l6_adv_concrete_kit_datagrabbers_w_bt',
        '5.0 MISCELLANEOUS' => 'parts_tsd_five_0_miscellaneous',
        'TOP ASSY,ORION C555, CONCRETE ,KIT, TESTED' => 'parts_tsd_top_assy_orion_c555_concrete_kit_tested',
        'TOP ASSY,ORION 910 KIT, TESTED' => 'parts_tsd_top_assy_orion_910_kit_tested',
        'TOP ASSY,ORION 920 KIT, TESTED' => 'parts_tsd_top_assy_orion_920_kit_tested',
        'TOP ASSY,ORION 930 KIT, TESTED' => 'parts_tsd_top_assy_orion_930_kit_tested',
        'TOP ASSY,ORION 940 KIT, TESTED' => 'parts_tsd_top_assy_orion_940_kit_tested',
        'TOP ASSY,ORION 950 KIT, TESTED' => 'parts_tsd_top_assy_orion_950_kit_tested',
        'METER REACH EXTENDER' => 'parts_tsd_meter_reach_extender',
        'TOP ASSY, L5300' => 'parts_tsd_top_assy_l5300',
        'TOP ASSY.,L601-3 (DF),TSTD' => 'parts_tsd_top_assy_l601_3_df_tstd',
        'TOP ASSY,L722 LONG STACK PROBE,TESTED' => 'parts_tsd_top_assy_l722_long_stack_probe_tested',
        'TOP ASSY,L722 SHORT STACK PROBE,TESTED' => 'parts_tsd_top_assy_l722_short_stack_probe_tested',
        'KIT, L5300+L722 LONG STACK PROBE' => 'parts_tsd_kit_l5300_l722_long_stack_probe',
    ];

    public function run(): void
    {
        $this->seedRulesFromTemplate(
            self::LEGACY_TEMPLATE_PATH,
            self::LEGACY_METADATA_SOURCE,
            fn (string $header, int &$packOccurrence) => self::LEGACY_CATEGORY_CODE_BY_NAME[$header] ?? null,
            fn (?string $current) => false,
            100,
        );

        $this->seedRulesFromTemplate(
            self::ORGANIZED_TEMPLATE_PATH,
            self::ORGANIZED_METADATA_SOURCE,
            fn (string $header, int &$packOccurrence) => $this->resolveOrganizedCategoryCode($header, $packOccurrence),
            fn (?string $current) => $current === 'parts_tsd_rhp_miscellaneous',
            200,
            self::ORGANIZED_SECTION_START_HEADER,
        );
    }

    private function seedRulesFromTemplate(
        string $templatePath,
        string $metadataSource,
        callable $resolveCategoryCode,
        callable $shouldClearCategoryOnMiscSubtotal,
        int $startingPriority,
        ?string $sectionStartHeader = null,
    ): void {
        if (! is_readable($templatePath)) {
            throw new RuntimeException('Parts & TSD mapping template CSV was not found at '.$templatePath);
        }

        $categoryIds = ProductCategory::query()->pluck('id', 'code');
        $rules = $this->buildRulesFromTemplate(
            $templatePath,
            $resolveCategoryCode,
            $shouldClearCategoryOnMiscSubtotal,
            $startingPriority,
            $sectionStartHeader,
        );
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
                        'source' => $metadataSource,
                        'category_code' => $rule['category_code'],
                        'unique_identifier' => $rule['unique_identifier'],
                    ],
                ],
            );
        }

        MappingRule::query()
            ->where('metadata->source', $metadataSource)
            ->whereNotIn('name', $ruleNames)
            ->update(['is_active' => false]);
    }

    /**
     * @return array<int, array{name: string, category_code: string|null, match_operator: string, pattern: string, priority: int, unique_identifier: string}>
     */
    private function buildRulesFromTemplate(
        string $templatePath,
        callable $resolveCategoryCode,
        callable $shouldClearCategoryOnMiscSubtotal,
        int $startingPriority,
        ?string $sectionStartHeader = null,
    ): array {
        $handle = fopen($templatePath, 'r');
        fgetcsv($handle);

        $currentCategoryCode = null;
        $packCategoryOccurrence = 0;
        $inTargetSection = $sectionStartHeader === null;
        /** @var array<string, list<string>> $itemsByCategory */
        $itemsByCategory = [];

        while (($row = fgetcsv($handle)) !== false) {
            $itemId = trim($row[0] ?? '');
            $description = trim($row[1] ?? '');
            $customerId = trim($row[2] ?? '');

            if (! $inTargetSection) {
                if ($itemId === $sectionStartHeader && $customerId === '' && $description === '') {
                    $inTargetSection = true;
                    $currentCategoryCode = $resolveCategoryCode($itemId, $packCategoryOccurrence);
                }

                continue;
            }

            if ($itemId === '') {
                $amount = trim($row[9] ?? '');

                if (strtolower($amount) === 'n/a' && $shouldClearCategoryOnMiscSubtotal($currentCategoryCode)) {
                    $currentCategoryCode = null;
                }

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

            $currentCategoryCode = $resolveCategoryCode($itemId, $packCategoryOccurrence);
        }

        fclose($handle);

        return $this->buildRulesFromGroupedItems($itemsByCategory, $startingPriority);
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

    private function resolveOrganizedCategoryCode(string $categoryName, int &$packCategoryOccurrence): ?string
    {
        if ($categoryName === 'PACK, 5 ,RAPID RH L6, SMART SENSOR') {
            $code = $packCategoryOccurrence === 0
                ? 'parts_tsd_pack_5_rapid_rh_l6_smart_sensor'
                : 'parts_tsd_pack_5_rapid_rh_l6_smart_sensor_reader';
            $packCategoryOccurrence++;

            return $code;
        }

        $mapped = self::ORGANIZED_CATEGORY_CODE_BY_NAME[$categoryName] ?? null;

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
