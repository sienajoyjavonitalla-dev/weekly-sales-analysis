<?php

namespace App\Domain\WeeklyAnalysis\Reports;

class WeeklyMeterModelCatalog
{
    /**
     * @return list<array{label:string, tracks_units:bool}>
     */
    public static function rows(): array
    {
        return [
            ['label' => 'RHP 4.0 Kits', 'tracks_units' => true],
            ['label' => 'RHP L6 Kits', 'tracks_units' => true],
            ['label' => 'RHP 4.0 Readr/Sens Pk', 'tracks_units' => true],
            ['label' => 'RHP L6 Readr/Sens Pk', 'tracks_units' => true],
            ['label' => 'RHP 4.0 5Pk Sensors*', 'tracks_units' => true],
            ['label' => 'RHP L6 5Pk Sensors*', 'tracks_units' => true],
            ['label' => 'RHP 4.0 Misc', 'tracks_units' => false],
            ['label' => 'RHP L6 Misc', 'tracks_units' => false],
            ['label' => 'BT Smart Loggers', 'tracks_units' => false],
            ['label' => 'RHP 4.0 Flooring Kits', 'tracks_units' => true],
            ['label' => 'RHP L6 Flooring Kits', 'tracks_units' => true],
            ['label' => 'True Remote Monitoring', 'tracks_units' => false],
            ['label' => 'Floor Sentry', 'tracks_units' => true],
            ['label' => '5.0 RHP Prod', 'tracks_units' => false],
            ['label' => 'C555 Concrete Kits', 'tracks_units' => true],
            ['label' => 'C575', 'tracks_units' => true],
            ['label' => 'BI 2200', 'tracks_units' => true],
            ['label' => 'MMI 1100', 'tracks_units' => true],
            ['label' => 'MMC205', 'tracks_units' => true],
            ['label' => 'L607', 'tracks_units' => true],
            ['label' => 'MMC210', 'tracks_units' => true],
            ['label' => 'MMC220', 'tracks_units' => true],
            ['label' => 'Orion 910', 'tracks_units' => true],
            ['label' => 'Orion 920', 'tracks_units' => true],
            ['label' => 'Orion 930', 'tracks_units' => true],
            ['label' => 'Orion 940', 'tracks_units' => true],
            ['label' => 'Orion 950', 'tracks_units' => true],
            ['label' => 'Handmeter Misc', 'tracks_units' => true],
            ['label' => 'L5200/L5300', 'tracks_units' => true],
            ['label' => 'L601-3', 'tracks_units' => true],
            ['label' => 'L610/L620', 'tracks_units' => true],
            ['label' => 'L612/L622', 'tracks_units' => true],
            ['label' => 'L722-L', 'tracks_units' => true],
            ['label' => 'L722-S', 'tracks_units' => true],
            ['label' => 'L622/L722 Kits', 'tracks_units' => true],
            ['label' => 'L5300/L722', 'tracks_units' => true],
        ];
    }

    /**
     * @return list<string>
     */
    public static function labels(): array
    {
        return array_column(self::rows(), 'label');
    }

    /**
     * Default product category code → weekly meter MODEL row.
     *
     * @return array<string, string>
     */
    public static function defaultCategoryCodeLabels(): array
    {
        return [
            'kit_rapid_rh_l6_starter_kit_plus_fahrenheit' => 'RHP L6 Kits',
            'parts_tsd_kit_rapid_rh_l6_starter_kit_plus_fahrenheit' => 'RHP L6 Kits',
            'pack_5_rapid_rh_l6_smart_sensor_reader' => 'RHP L6 Readr/Sens Pk',
            'parts_tsd_pack_5_rapid_rh_l6_smart_sensor_reader' => 'RHP L6 Readr/Sens Pk',
            'pack_5_rapid_rh_l6_smart_sensor' => 'RHP L6 5Pk Sensors*',
            'parts_tsd_pack_5_rapid_rh_l6_smart_sensor' => 'RHP L6 5Pk Sensors*',
            'value_pack_25pc_rhp_l6_smart_sensor' => 'RHP L6 5Pk Sensors*',
            'parts_tsd_value_pack_25pc_rhp_l6_smart_sensor' => 'RHP L6 5Pk Sensors*',
            'value_pack_50pc_rhp_l6_smart_sensor' => 'RHP L6 5Pk Sensors*',
            'parts_tsd_value_pack_50pc_rhp_l6_smart_sensor' => 'RHP L6 5Pk Sensors*',
            'super_value_pack_rapid_rh_l6_smart_sensor' => 'RHP L6 5Pk Sensors*',
            'parts_tsd_super_value_pack_rapid_rh_l6_smart_sensor' => 'RHP L6 5Pk Sensors*',
            'wfp400_rapid_rh_l6_professional_flooring_installer_kit' => 'RHP L6 Flooring Kits',
            'wfp450_rrh_l6_pro_floor_installer_kit_dg_w_bt' => 'RHP L6 Flooring Kits',
            'wfp350_rapid_rh_l6_adv_concrete_kit_datagrabbers_w_bt' => 'RHP L6 Flooring Kits',
            'wfp350_rapid_rh_l6_adv_concrete_kit_datagrabbers_w_bt_2' => 'RHP L6 Flooring Kits',
            'wfp500_reusable_pro_floor_installer_package_celsius_950' => 'RHP L6 Flooring Kits',
            'rhp_miscellaneous' => 'RHP L6 Misc',
            'parts_tsd_rhp_miscellaneous' => 'RHP L6 Misc',
            'kit_upgrade' => 'RHP L6 Misc',
            'parts_tsd_kit_upgrade' => 'RHP L6 Misc',
            'floor_sentry' => 'Floor Sentry',
            'parts_tsd_floor_sentry' => 'Floor Sentry',
            'true_remote' => 'True Remote Monitoring',
            'rapid_rh_5_0_complete_starter_kit_plus_fahrenheit' => '5.0 RHP Prod',
            '5_0_miscellaneous' => '5.0 RHP Prod',
            'top_assy_orion_c555_concrete_kit_tested' => 'C555 Concrete Kits',
            'parts_tsd_top_assy_orion_c555_concrete_kit_tested' => 'C555 Concrete Kits',
            'top_assy_orion_910_kit_tested' => 'Orion 910',
            'parts_tsd_top_assy_orion_910_kit_tested' => 'Orion 910',
            'top_assy_orion_920_kit_tested' => 'Orion 920',
            'parts_tsd_top_assy_orion_920_kit_tested' => 'Orion 920',
            'top_assy_orion_930_kit_tested' => 'Orion 930',
            'parts_tsd_top_assy_orion_930_kit_tested' => 'Orion 930',
            'top_assy_orion_940_kit_tested' => 'Orion 940',
            'parts_tsd_top_assy_orion_940_kit_tested' => 'Orion 940',
            'top_assy_orion_950_kit_tested' => 'Orion 950',
            'parts_tsd_top_assy_orion_950_kit_tested' => 'Orion 950',
            'meter_reach_extender' => 'Handmeter Misc',
            'parts_tsd_meter_reach_extender' => 'Handmeter Misc',
            'top_assy_l5300' => 'L5200/L5300',
            'parts_tsd_top_assy_l5300' => 'L5200/L5300',
            'top_assy_l601_3_df_tstd' => 'L601-3',
            'top_assy_l722_long_stack_probe_tested' => 'L722-L',
            'top_assy_l722_short_stack_probe_tested' => 'L722-S',
            'kit_l5300_l722_stack_probe' => 'L5300/L722',
            'parts_tsd_kit_l5300_l722_long_stack_probe' => 'L5300/L722',
        ];
    }
}
