<?php

namespace Database\Seeders;

use App\Models\MappingRule;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class MappingRuleSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIds = ProductCategory::query()->pluck('id', 'code');

        $rules = [
            [
                'name' => 'Amazon customer rows',
                'product_category_id' => $categoryIds['amazon_sales'] ?? null,
                'source_type' => 'sales_analysis',
                'match_field' => 'customer_name',
                'match_operator' => 'contains',
                'pattern' => 'AMAZON',
                'target_bucket' => 'rhp',
                'priority' => 10,
            ],
            [
                'name' => 'Amazon sales rep rows',
                'product_category_id' => $categoryIds['amazon_sales'] ?? null,
                'source_type' => 'sales_analysis',
                'match_field' => 'sales_rep_id',
                'match_operator' => 'exact',
                'pattern' => 'AMA',
                'target_bucket' => 'rhp',
                'priority' => 11,
            ],
            [
                'name' => 'RHP item prefix',
                'product_category_id' => $categoryIds['rapid_rh'] ?? null,
                'source_type' => 'sales_analysis',
                'match_field' => 'item_id',
                'match_operator' => 'starts_with',
                'pattern' => '880-R',
                'target_bucket' => 'rhp',
                'priority' => 20,
            ],
            [
                'name' => 'Concrete item prefix',
                'product_category_id' => $categoryIds['concrete'] ?? null,
                'source_type' => 'sales_analysis',
                'match_field' => 'item_id',
                'match_operator' => 'starts_with',
                'pattern' => 'C',
                'target_bucket' => 'rhp',
                'priority' => 40,
            ],
            [
                'name' => 'Repair item prefix',
                'product_category_id' => $categoryIds['ts_repairs'] ?? null,
                'source_type' => 'sales_analysis',
                'match_field' => 'item_id',
                'match_operator' => 'starts_with',
                'pattern' => 'REP-',
                'target_bucket' => 'parts_tsd',
                'priority' => 50,
            ],
            [
                'name' => 'Field service rows',
                'product_category_id' => $categoryIds['ts_repairs'] ?? null,
                'source_type' => 'sales_analysis',
                'match_field' => 'description',
                'match_operator' => 'contains',
                'pattern' => 'FIELD SERVICE',
                'target_bucket' => 'parts_tsd',
                'priority' => 55,
            ],
            [
                'name' => 'Shipping rows',
                'product_category_id' => $categoryIds['misc'] ?? null,
                'source_type' => 'sales_analysis',
                'match_field' => 'item_id',
                'match_operator' => 'starts_with',
                'pattern' => 'SHIP_',
                'target_bucket' => 'parts_tsd',
                'priority' => 80,
            ],
            [
                'name' => 'Generic parts rows',
                'product_category_id' => $categoryIds['part_sales'] ?? null,
                'source_type' => 'sales_analysis',
                'match_field' => 'item_id',
                'match_operator' => 'regex',
                'pattern' => '/^(648|668|674|681|712|720|809|810|811|830|840|860)-/',
                'target_bucket' => 'parts_tsd',
                'priority' => 100,
            ],
        ];

        foreach ($rules as $rule) {
            MappingRule::updateOrCreate(
                ['name' => $rule['name']],
                $rule + ['is_active' => true]
            );
        }
    }
}
