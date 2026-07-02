<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PartsTsdProductCategorySeeder extends Seeder
{
    /**
     * Parts & TSD Sales Analysis categories from the organized workbook template.
     * Sort order starts at 100 and increments by 4 to leave three slots between entries.
     *
     * @var array<int, array{code: string, name: string, report_family: string}>
     */
    private const PARTS_TSD_CATEGORIES = [
        ['code' => 'misc_on_total_sales_report', 'name' => 'Misc (on Total Sales Report)', 'report_family' => 'parts_tsd'],
        ['code' => 'tsd_parts', 'name' => 'TSD Parts', 'report_family' => 'parts_tsd'],
        ['code' => 'tsd_repairs', 'name' => 'TSD Repairs', 'report_family' => 'parts_tsd'],
    ];

    public function run(): void
    {
        $codes = [];

        foreach (self::PARTS_TSD_CATEGORIES as $index => $category) {
            $sortOrder = 100 + ($index * 4);
            $codes[] = $category['code'];

            ProductCategory::updateOrCreate(
                ['code' => $category['code']],
                [
                    'name' => $category['name'],
                    'report_family' => $category['report_family'],
                    'sales_analysis_bucket' => 'parts_tsd',
                    'total_sales_row_label' => $category['name'],
                    'weekly_meter_row_label' => null,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                    'metadata' => [
                        'source' => 'parts_tsd_sales_analysis_template',
                        'slug' => Str::slug($category['name']),
                    ],
                ],
            );
        }

        ProductCategory::query()
            ->where('metadata->source', 'parts_tsd_sales_analysis_template')
            ->whereNotIn('code', $codes)
            ->update(['is_active' => false]);
    }
}
