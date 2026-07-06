<?php

namespace App\Domain\WeeklyAnalysis\Classification\Support;

use App\Models\ProductCategory;

class MappingRuleCategoryResolver
{
    public function resolve(?ProductCategory $category, ?string $targetBucket): ?ProductCategory
    {
        if ($category === null) {
            return null;
        }

        $bucket = $targetBucket ?: 'raw';

        if ($category->sales_analysis_bucket === $bucket) {
            return $category;
        }

        if (! in_array($bucket, ['rhp', 'parts_tsd'], true)) {
            return $category;
        }

        return ProductCategory::query()
            ->where('name', $category->name)
            ->where('sales_analysis_bucket', $bucket)
            ->where('is_active', true)
            ->first() ?? $category;
    }
}
