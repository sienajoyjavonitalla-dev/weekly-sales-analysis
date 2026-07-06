<?php

namespace App\Rules;

use App\Domain\WeeklyAnalysis\Classification\Support\SalesAnalysisBucketPolicy;
use App\Models\MappingRule;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueMappingRuleSignature implements ValidationRule
{
    public function __construct(
        private readonly ?int $ignoreRuleId = null,
        private readonly ?string $sourceType = null,
        private readonly ?string $matchField = null,
        private readonly ?string $matchOperator = null,
        private readonly ?string $pattern = null,
        private readonly ?string $targetBucket = null,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $sourceType = $this->sourceType ?? request()->input('source_type');
        $matchField = $this->matchField ?? request()->input('match_field');
        $matchOperator = $this->matchOperator ?? request()->input('match_operator');
        $pattern = $this->pattern ?? request()->input('pattern');
        $targetBucket = $this->targetBucket ?? request()->input('target_bucket');

        if (! is_string($sourceType) || ! is_string($matchField) || ! is_string($matchOperator) || ! is_string($pattern)) {
            return;
        }

        $conflicts = MappingRule::query()
            ->where('source_type', $sourceType)
            ->where('match_field', $matchField)
            ->where('match_operator', $matchOperator)
            ->where('pattern', $pattern)
            ->where('is_active', true)
            ->when($this->ignoreRuleId !== null, fn ($query) => $query->where('id', '!=', $this->ignoreRuleId))
            ->get()
            ->contains(fn (MappingRule $rule): bool => SalesAnalysisBucketPolicy::mappingRulesConflict(
                $rule->target_bucket,
                is_string($targetBucket) ? $targetBucket : null,
            ));

        if ($conflicts) {
            $fail('A mapping rule with this source type, field, operator, and pattern already exists for an overlapping sales analysis bucket.');
        }
    }
}
