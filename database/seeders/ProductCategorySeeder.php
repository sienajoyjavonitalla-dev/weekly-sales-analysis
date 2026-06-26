<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'code' => 'inline',
                'name' => 'In-Line',
                'report_family' => 'total_sales',
                'sales_analysis_bucket' => 'parts_tsd',
                'total_sales_row_label' => 'In-Line',
                'weekly_meter_row_label' => null,
                'sort_order' => 10,
            ],
            [
                'code' => 'large_wood_meters',
                'name' => 'Large Wood Meters',
                'report_family' => 'meter',
                'sales_analysis_bucket' => 'rhp',
                'total_sales_row_label' => 'Lg Wood Mtrs',
                'weekly_meter_row_label' => 'L5300/L722',
                'sort_order' => 20,
            ],
            [
                'code' => 'small_wood_meters',
                'name' => 'Small Wood Meters',
                'report_family' => 'meter',
                'sales_analysis_bucket' => 'rhp',
                'total_sales_row_label' => 'Sm Wood Mtrs',
                'weekly_meter_row_label' => 'Orion 910',
                'sort_order' => 30,
            ],
            [
                'code' => 'rapid_rh',
                'name' => 'Rapid RH',
                'report_family' => 'rhp',
                'sales_analysis_bucket' => 'rhp',
                'total_sales_row_label' => 'Rapid RH',
                'weekly_meter_row_label' => 'RHP L6 5Pk Sensors*',
                'sort_order' => 40,
            ],
            [
                'code' => 'concrete',
                'name' => 'Concrete',
                'report_family' => 'meter',
                'sales_analysis_bucket' => 'rhp',
                'total_sales_row_label' => 'Concrete',
                'weekly_meter_row_label' => 'C555 Concrete Kits',
                'sort_order' => 50,
            ],
            [
                'code' => 'handmeter_misc',
                'name' => 'Handmeter Misc',
                'report_family' => 'meter',
                'sales_analysis_bucket' => 'rhp',
                'total_sales_row_label' => 'Handmeter Misc',
                'weekly_meter_row_label' => 'Handmeter Misc',
                'sort_order' => 60,
            ],
            [
                'code' => 'part_sales',
                'name' => 'Part Sales',
                'report_family' => 'parts_tsd',
                'sales_analysis_bucket' => 'parts_tsd',
                'total_sales_row_label' => 'Part Sales',
                'weekly_meter_row_label' => null,
                'sort_order' => 70,
            ],
            [
                'code' => 'ts_repairs',
                'name' => 'T/S Repairs',
                'report_family' => 'parts_tsd',
                'sales_analysis_bucket' => 'parts_tsd',
                'total_sales_row_label' => 'T/S',
                'weekly_meter_row_label' => null,
                'sort_order' => 80,
            ],
            [
                'code' => 'misc',
                'name' => 'Miscellaneous',
                'report_family' => 'total_sales',
                'sales_analysis_bucket' => 'parts_tsd',
                'total_sales_row_label' => 'Misc',
                'weekly_meter_row_label' => null,
                'sort_order' => 90,
            ],
            [
                'code' => 'internet_sales',
                'name' => 'Internet Sales',
                'report_family' => 'channel',
                'sales_analysis_bucket' => 'rhp',
                'total_sales_row_label' => 'Internet Sales',
                'weekly_meter_row_label' => null,
                'sort_order' => 100,
            ],
            [
                'code' => 'amazon_sales',
                'name' => 'Amazon Sales',
                'report_family' => 'channel',
                'sales_analysis_bucket' => 'rhp',
                'total_sales_row_label' => 'Amazon Sales',
                'weekly_meter_row_label' => null,
                'sort_order' => 110,
            ],
        ];

        foreach ($categories as $category) {
            ProductCategory::updateOrCreate(
                ['code' => $category['code']],
                $category + ['is_active' => true]
            );
        }
    }
}
