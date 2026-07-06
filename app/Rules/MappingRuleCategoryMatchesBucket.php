<?php

namespace App\Rules;

use App\Models\ProductCategory;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MappingRuleCategoryMatchesBucket implements ValidationRule
{
    public function __construct(
        private readonly ?string $targetBucket = null,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $targetBucket = $this->targetBucket ?? request()->input('target_bucket');

        if (! is_string($targetBucket) || ! in_array($targetBucket, ['rhp', 'parts_tsd'], true)) {
            return;
        }

        $category = ProductCategory::query()->find($value);

        if ($category === null) {
            return;
        }

        if ($category->sales_analysis_bucket !== $targetBucket) {
            $fail('The selected category belongs to the '.strtoupper($category->sales_analysis_bucket ?? 'unknown').' bucket, but this rule targets '.strtoupper($targetBucket).'. Choose a matching category.');
        }
    }
}
