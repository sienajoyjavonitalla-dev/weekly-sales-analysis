<?php

namespace App\Domain\WeeklyAnalysis\Classification\Data;

use App\Models\MappingRule;
use App\Models\ProductCategory;

class ClassificationMatch
{
    public function __construct(
        public readonly MappingRule $mappingRule,
        public readonly ?ProductCategory $productCategory,
        public readonly ?string $targetBucket,
    ) {
    }
}
