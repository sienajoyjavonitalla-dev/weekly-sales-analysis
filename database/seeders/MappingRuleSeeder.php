<?php

namespace Database\Seeders;

use App\Models\MappingRule;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use RuntimeException;

class MappingRuleSeeder extends Seeder
{
    public function run(): void
    {
        if (MappingRule::query()->exists()) {
            $this->command?->info('mapping_rules already has data; skipping.');

            return;
        }

        $path = database_path('data/seeders/mapping_rules.json');

        if (! is_file($path)) {
            throw new RuntimeException("Mapping rule snapshot missing: {$path}");
        }

        /** @var array<int, array<string, mixed>> $rules */
        $rules = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $categoryIds = ProductCategory::query()->pluck('id', 'code');

        foreach ($rules as $rule) {
            $categoryCode = $rule['category_code'] ?? null;

            MappingRule::query()->create([
                'name' => $rule['name'],
                'product_category_id' => $categoryCode
                    ? ($categoryIds[$categoryCode] ?? null)
                    : null,
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

        $this->command?->info('Seeded '.count($rules).' mapping rules.');
    }
}
