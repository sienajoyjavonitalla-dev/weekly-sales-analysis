<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RhpProductCategorySeeder extends Seeder
{
    /**
     * RHP Sales Analysis categories from the organized workbook template.
     * Sort order starts at 10 and increments by 4 to leave three slots between entries.
     *
     * @var array<int, array{code: string, name: string, report_family: string}>
     */
    private const RHP_CATEGORIES = [
        ['code' => 'rhp_miscellaneous', 'name' => 'RHP MISCELLANEOUS', 'report_family' => 'rhp'],
        ['code' => 'pack_5_rapid_rh_l6_smart_sensor', 'name' => 'PACK, 5 ,RAPID RH L6, SMART SENSOR', 'report_family' => 'rhp'],
        ['code' => 'floor_sentry', 'name' => 'Floor Sentry', 'report_family' => 'rhp'],
        ['code' => 'kit_upgrade', 'name' => 'KIT,UPGRADE', 'report_family' => 'rhp'],
        ['code' => 'pack_5_rapid_rh_l6_smart_sensor_reader', 'name' => 'PACK, 5 ,RAPID RH L6, SMART SENSOR', 'report_family' => 'rhp'],
        ['code' => 'kit_rapid_rh_l6_starter_kit_plus_fahrenheit', 'name' => 'KIT, RAPID RH L6 STARTER KIT PLUS-FAHRENHEIT', 'report_family' => 'rhp'],
        ['code' => 'value_pack_25pc_rhp_l6_smart_sensor', 'name' => 'VALUE PACK, 25PC, RHP L6 SMART SENSOR', 'report_family' => 'rhp'],
        ['code' => 'value_pack_50pc_rhp_l6_smart_sensor', 'name' => 'VALUE PACK, 50PC, RHP L6 SMART SENSOR', 'report_family' => 'rhp'],
        ['code' => 'super_value_pack_rapid_rh_l6_smart_sensor', 'name' => 'SUPER VALUE PACK,RAPID RH L6, SMART SENSOR', 'report_family' => 'rhp'],
        ['code' => 'wfp400_rapid_rh_l6_professional_flooring_installer_kit', 'name' => 'WFP400+ RAPID RH L6 PROFESSIONAL FLOORING INSTALLER KIT', 'report_family' => 'rhp'],
        ['code' => 'wfp450_rrh_l6_pro_floor_installer_kit_dg_w_bt', 'name' => 'WFP450+ RRH® L6 Pro Floor Installer Kit + DG w/BT', 'report_family' => 'rhp'],
        ['code' => 'rapid_rh_5_0_complete_starter_kit_plus_fahrenheit', 'name' => 'Rapid RH 5.0 Complete Starter Kit Plus-Fahrenheit', 'report_family' => 'rhp'],
        ['code' => 'top_assy_orion_c555_concrete_kit_tested', 'name' => 'TOP ASSY,ORION C555, CONCRETE ,KIT, TESTED', 'report_family' => 'meter'],
        ['code' => 'top_assy_orion_910_kit_tested', 'name' => 'TOP ASSY,ORION 910 KIT, TESTED', 'report_family' => 'meter'],
        ['code' => 'top_assy_orion_920_kit_tested', 'name' => 'TOP ASSY,ORION 920 KIT, TESTED', 'report_family' => 'meter'],
        ['code' => 'top_assy_orion_930_kit_tested', 'name' => 'TOP ASSY,ORION 930 KIT, TESTED', 'report_family' => 'meter'],
        ['code' => 'top_assy_orion_940_kit_tested', 'name' => 'TOP ASSY,ORION 940 KIT, TESTED', 'report_family' => 'meter'],
        ['code' => 'top_assy_orion_950_kit_tested', 'name' => 'TOP ASSY,ORION 950 KIT, TESTED', 'report_family' => 'meter'],
        ['code' => 'meter_reach_extender', 'name' => 'METER REACH EXTENDER', 'report_family' => 'meter'],
        ['code' => 'top_assy_l5300', 'name' => 'TOP ASSY, L5300', 'report_family' => 'meter'],
        ['code' => 'kit_l5300_l722_stack_probe', 'name' => 'KIT, L5300+L722 STACK PROBE', 'report_family' => 'meter'],
    ];

    public function run(): void
    {
        $codes = [];

        foreach (self::RHP_CATEGORIES as $index => $category) {
            $sortOrder = 10 + ($index * 4);
            $codes[] = $category['code'];

            ProductCategory::updateOrCreate(
                ['code' => $category['code']],
                [
                    'name' => $category['name'],
                    'report_family' => $category['report_family'],
                    'sales_analysis_bucket' => 'rhp',
                    'total_sales_row_label' => $category['name'],
                    'weekly_meter_row_label' => $this->weeklyMeterRowLabel($category['name']),
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                    'metadata' => [
                        'source' => 'rhp_sales_analysis_template',
                        'slug' => Str::slug($category['name']),
                    ],
                ],
            );
        }

        ProductCategory::query()
            ->where('metadata->source', 'rhp_sales_analysis_template')
            ->whereNotIn('code', $codes)
            ->update(['is_active' => false]);
    }

    private function weeklyMeterRowLabel(string $name): ?string
    {
        return match ($name) {
            'TOP ASSY, L5300' => 'L5300/L722',
            'KIT, L5300+L722 STACK PROBE' => 'L5300/L722',
            'TOP ASSY,ORION 910 KIT, TESTED' => 'Orion 910',
            default => null,
        };
    }
}
