<?php

namespace Database\Seeders;

use App\Models\MappingRule;
use App\Models\ProductCategory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $this->ensureProductCategoryForeignKeys();

        if (ProductCategory::query()->exists() || MappingRule::query()->exists()) {
            $this->command?->info('product_categories or mapping_rules already has data; skipping.');

            return;
        }

        $categories = $this->loadJson('product_categories.json');
        $rules = $this->loadJson('mapping_rules.json');

        if ($categories === []) {
            throw new RuntimeException('Product category snapshot is empty.');
        }

        /** @var array<string, list<array<string, mixed>>> $rulesByCategoryCode */
        $rulesByCategoryCode = [];
        /** @var list<array<string, mixed>> $uncategorizedRules */
        $uncategorizedRules = [];

        foreach ($rules as $rule) {
            $categoryCode = $rule['category_code'] ?? null;

            if ($categoryCode === null || $categoryCode === '') {
                $uncategorizedRules[] = $rule;
                continue;
            }

            $rulesByCategoryCode[$categoryCode][] = $rule;
        }

        $now = now();
        $maxCategoryId = 0;
        $ruleCount = 0;

        foreach ($categories as $category) {
            if (! isset($category['id'], $category['code'])) {
                throw new RuntimeException('Product category snapshot row missing id or code.');
            }

            $categoryId = (int) $category['id'];
            $categoryCode = (string) $category['code'];
            $maxCategoryId = max($maxCategoryId, $categoryId);

            ProductCategory::query()->insert([
                'id' => $categoryId,
                'code' => $categoryCode,
                'name' => $category['name'],
                'report_family' => $category['report_family'],
                'sales_analysis_bucket' => $category['sales_analysis_bucket'] ?: null,
                'total_sales_row_label' => $category['total_sales_row_label'] ?: null,
                'weekly_meter_row_label' => $category['weekly_meter_row_label'] ?: null,
                'sort_order' => (int) $category['sort_order'],
                'is_active' => (bool) $category['is_active'],
                'metadata' => isset($category['metadata'])
                    ? json_encode($category['metadata'], JSON_THROW_ON_ERROR)
                    : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($rulesByCategoryCode[$categoryCode] ?? [] as $rule) {
                $this->createMappingRule($rule, $categoryId);
                $ruleCount++;
            }

            unset($rulesByCategoryCode[$categoryCode]);
        }

        if ($rulesByCategoryCode !== []) {
            $missing = implode(', ', array_keys($rulesByCategoryCode));
            throw new RuntimeException("Mapping rules reference missing product category codes: {$missing}");
        }

        foreach ($uncategorizedRules as $rule) {
            $this->createMappingRule($rule, null);
            $ruleCount++;
        }

        if ($maxCategoryId > 0 && DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE product_categories AUTO_INCREMENT = '.($maxCategoryId + 1));
        }

        $this->command?->info('Seeded '.count($categories)." product categories and {$ruleCount} mapping rules.");
    }

    /**
     * Ensure product_category_id FKs reference product_categories (not a renamed backup table).
     */
    private function ensureProductCategoryForeignKeys(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! Schema::hasTable('product_categories')) {
            return;
        }

        $tables = ['mapping_rules', 'sales_rows'];

        if (Schema::hasTable('sales_row_state_placements')) {
            $tables[] = 'sales_row_state_placements';
        }

        $database = DB::getDatabaseName();

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $referencedTable = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('CONSTRAINT_SCHEMA', $database)
                ->where('TABLE_NAME', $table)
                ->where('COLUMN_NAME', 'product_category_id')
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->value('REFERENCED_TABLE_NAME');

            if ($referencedTable === 'product_categories') {
                continue;
            }

            if ($referencedTable !== null) {
                $this->command?->warn("{$table}: retargeting product_category_id FK from {$referencedTable} to product_categories");

                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->dropForeign(['product_category_id']);
                });
            }

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->foreign('product_category_id')
                    ->references('id')
                    ->on('product_categories')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function createMappingRule(array $rule, ?int $productCategoryId): void
    {
        MappingRule::query()->create([
            'name' => $rule['name'],
            'product_category_id' => $productCategoryId,
            'source_type' => $rule['source_type'],
            'match_field' => $rule['match_field'],
            'match_operator' => $rule['match_operator'],
            'pattern' => $rule['pattern'],
            'target_bucket' => $rule['target_bucket'] ?: null,
            'priority' => (int) $rule['priority'],
            'is_active' => (bool) $rule['is_active'],
            'metadata' => $rule['metadata'] ?? null,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadJson(string $filename): array
    {
        $path = database_path('data/seeders/'.$filename);

        if (! is_file($path)) {
            throw new RuntimeException("Seeder snapshot missing: {$path}");
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return $rows;
    }
}
