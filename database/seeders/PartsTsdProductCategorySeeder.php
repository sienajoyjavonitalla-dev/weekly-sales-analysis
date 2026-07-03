<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PartsTsdProductCategorySeeder extends Seeder
{
    /**
     * Legacy Parts & TSD Sales Analysis categories (Misc / TSD Parts / TSD Repairs sheet).
     * Sort order starts at 100 and increments by 4.
     *
     * @var array<int, array{code: string, name: string, report_family: string}>
     */
    private const LEGACY_PARTS_TSD_CATEGORIES = [
        ['code' => 'misc_on_total_sales_report', 'name' => 'Misc (on Total Sales Report)', 'report_family' => 'parts_tsd'],
        ['code' => 'tsd_parts', 'name' => 'TSD Parts', 'report_family' => 'parts_tsd'],
        ['code' => 'tsd_repairs', 'name' => 'TSD Repairs', 'report_family' => 'parts_tsd'],
    ];

    /**
     * Organized workbook Parts & TSD categories (RHP-style product groupings).
     * Sort order starts at 200 and increments by 4.
     *
     * @var array<int, array{code: string, name: string, report_family: string}>
     */
    private const ORGANIZED_PARTS_TSD_CATEGORIES = [
        ['code' => 'parts_tsd_rhp_miscellaneous', 'name' => 'RHP MISCELLANEOUS', 'report_family' => 'rhp'],
        ['code' => 'parts_tsd_pack_5_rapid_rh_l6_smart_sensor', 'name' => 'PACK, 5 ,RAPID RH L6, SMART SENSOR', 'report_family' => 'rhp'],
        ['code' => 'parts_tsd_pack_5_rapid_rh_l6_smart_sensor_reader', 'name' => 'PACK, 5 ,RAPID RH L6, SMART SENSOR', 'report_family' => 'rhp'],
        ['code' => 'parts_tsd_true_remote', 'name' => 'TRUE REMOTE', 'report_family' => 'rhp'],
        ['code' => 'parts_tsd_kit_upgrade', 'name' => 'KIT,UPGRADE', 'report_family' => 'rhp'],
        ['code' => 'parts_tsd_floor_sentry', 'name' => 'Floor Sentry', 'report_family' => 'rhp'],
        ['code' => 'parts_tsd_kit_rapid_rh_l6_starter_kit_plus_fahrenheit', 'name' => 'KIT, RAPID RH L6 STARTER KIT PLUS-FAHRENHEIT', 'report_family' => 'rhp'],
        ['code' => 'parts_tsd_value_pack_25pc_rhp_l6_smart_sensor', 'name' => 'VALUE PACK, 25PC, RHP L6 SMART SENSOR', 'report_family' => 'rhp'],
        ['code' => 'parts_tsd_value_pack_50pc_rhp_l6_smart_sensor', 'name' => 'VALUE PACK, 50PC, RHP L6 SMART SENSOR', 'report_family' => 'rhp'],
        ['code' => 'parts_tsd_super_value_pack_rapid_rh_l6_smart_sensor', 'name' => 'SUPER VALUE PACK,RAPID RH L6, SMART SENSOR', 'report_family' => 'rhp'],
        ['code' => 'parts_tsd_wfp350_rapid_rh_l6_adv_concrete_kit_datagrabbers_w_bt', 'name' => 'WFP350+ Rapid RH® L6 Adv. Concrete Kit + DataGrabbers w/BT', 'report_family' => 'rhp'],
        ['code' => 'parts_tsd_five_0_miscellaneous', 'name' => '5.0 MISCELLANEOUS', 'report_family' => 'rhp'],
        ['code' => 'parts_tsd_top_assy_orion_c555_concrete_kit_tested', 'name' => 'TOP ASSY,ORION C555, CONCRETE ,KIT, TESTED', 'report_family' => 'meter'],
        ['code' => 'parts_tsd_top_assy_orion_910_kit_tested', 'name' => 'TOP ASSY,ORION 910 KIT, TESTED', 'report_family' => 'meter'],
        ['code' => 'parts_tsd_top_assy_orion_920_kit_tested', 'name' => 'TOP ASSY,ORION 920 KIT, TESTED', 'report_family' => 'meter'],
        ['code' => 'parts_tsd_top_assy_orion_930_kit_tested', 'name' => 'TOP ASSY,ORION 930 KIT, TESTED', 'report_family' => 'meter'],
        ['code' => 'parts_tsd_top_assy_orion_940_kit_tested', 'name' => 'TOP ASSY,ORION 940 KIT, TESTED', 'report_family' => 'meter'],
        ['code' => 'parts_tsd_top_assy_orion_950_kit_tested', 'name' => 'TOP ASSY,ORION 950 KIT, TESTED', 'report_family' => 'meter'],
        ['code' => 'parts_tsd_meter_reach_extender', 'name' => 'METER REACH EXTENDER', 'report_family' => 'meter'],
        ['code' => 'parts_tsd_top_assy_l5300', 'name' => 'TOP ASSY, L5300', 'report_family' => 'meter'],
        ['code' => 'parts_tsd_top_assy_l601_3_df_tstd', 'name' => 'TOP ASSY.,L601-3 (DF),TSTD', 'report_family' => 'meter'],
        ['code' => 'parts_tsd_top_assy_l722_long_stack_probe_tested', 'name' => 'TOP ASSY,L722 LONG STACK PROBE,TESTED', 'report_family' => 'meter'],
        ['code' => 'parts_tsd_top_assy_l722_short_stack_probe_tested', 'name' => 'TOP ASSY,L722 SHORT STACK PROBE,TESTED', 'report_family' => 'meter'],
        ['code' => 'parts_tsd_kit_l5300_l722_long_stack_probe', 'name' => 'KIT, L5300+L722 LONG STACK PROBE', 'report_family' => 'meter'],
    ];

    public function run(): void
    {
        $this->seedCategoryGroup(
            self::LEGACY_PARTS_TSD_CATEGORIES,
            'parts_tsd_legacy_sales_analysis_template',
            100,
        );

        $this->seedCategoryGroup(
            self::ORGANIZED_PARTS_TSD_CATEGORIES,
            'parts_tsd_sales_analysis_template',
            200,
        );
    }

    /**
     * @param  array<int, array{code: string, name: string, report_family: string}>  $categories
     */
    private function seedCategoryGroup(array $categories, string $metadataSource, int $startingSortOrder): void
    {
        $codes = [];

        foreach ($categories as $index => $category) {
            $sortOrder = $startingSortOrder + ($index * 4);
            $codes[] = $category['code'];

            ProductCategory::updateOrCreate(
                ['code' => $category['code']],
                [
                    'name' => $category['name'],
                    'report_family' => $category['report_family'],
                    'sales_analysis_bucket' => 'parts_tsd',
                    'total_sales_row_label' => $category['name'],
                    'weekly_meter_row_label' => $this->weeklyMeterRowLabel($category['name']),
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                    'metadata' => [
                        'source' => $metadataSource,
                        'slug' => Str::slug($category['name']),
                    ],
                ],
            );
        }

        ProductCategory::query()
            ->where('metadata->source', $metadataSource)
            ->whereNotIn('code', $codes)
            ->update(['is_active' => false]);
    }

    private function weeklyMeterRowLabel(string $name): ?string
    {
        return match ($name) {
            'TOP ASSY, L5300' => 'L5300/L722',
            'TOP ASSY.,L601-3 (DF),TSTD' => 'L5300/L722',
            'TOP ASSY,L722 LONG STACK PROBE,TESTED' => 'L5300/L722',
            'TOP ASSY,L722 SHORT STACK PROBE,TESTED' => 'L5300/L722',
            'KIT, L5300+L722 LONG STACK PROBE' => 'L5300/L722',
            'TOP ASSY,ORION 910 KIT, TESTED' => 'Orion 910',
            default => null,
        };
    }
}
