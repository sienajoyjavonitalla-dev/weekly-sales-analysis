<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use RuntimeException;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        if (ProductCategory::query()->exists()) {
            $this->command?->info('product_categories already has data; skipping.');

            return;
        }

        $path = database_path('data/seeders/product_categories.json');

        if (! is_file($path)) {
            throw new RuntimeException("Product category snapshot missing: {$path}");
        }

        /** @var array<int, array<string, mixed>> $categories */
        $categories = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        if ($categories === []) {
            throw new RuntimeException('Product category snapshot is empty.');
        }

        foreach ($categories as $category) {
            ProductCategory::query()->create([
                'code' => $category['code'],
                'name' => $category['name'],
                'report_family' => $category['report_family'],
                'sales_analysis_bucket' => $category['sales_analysis_bucket'] ?: null,
                'total_sales_row_label' => $category['total_sales_row_label'] ?: null,
                'weekly_meter_row_label' => $category['weekly_meter_row_label'] ?: null,
                'sort_order' => (int) $category['sort_order'],
                'is_active' => (bool) $category['is_active'],
                'metadata' => $category['metadata'] ?? null,
            ]);
        }

        $this->command?->info('Seeded '.count($categories).' product categories.');
    }
}
