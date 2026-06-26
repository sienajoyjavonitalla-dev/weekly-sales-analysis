<?php

namespace App\Domain\WeeklyAnalysis\Classification\Services;

use App\Domain\WeeklyAnalysis\Classification\Data\ClassificationMatch;
use App\Models\MappingRule;
use App\Models\SalesRow;
use Illuminate\Database\Eloquent\Collection;

class MappingRuleMatcher
{
    /**
     * @var array<int, string>
     */
    private const MATCHABLE_FIELDS = [
        'item_id',
        'description',
        'customer_id',
        'customer_name',
        'invoice_number',
        'sales_rep_id',
        'country',
        'bill_to_state',
    ];

    /**
     * @param  Collection<int, MappingRule>|null  $rules
     */
    public function match(SalesRow $salesRow, ?Collection $rules = null): ?ClassificationMatch
    {
        $rules ??= MappingRule::query()
            ->with('productCategory')
            ->where('source_type', 'sales_analysis')
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('name')
            ->get();

        foreach ($rules as $rule) {
            if (! $this->ruleMatches($salesRow, $rule)) {
                continue;
            }

            return new ClassificationMatch(
                mappingRule: $rule,
                productCategory: $rule->productCategory,
                targetBucket: $rule->target_bucket ?? $rule->productCategory?->sales_analysis_bucket,
            );
        }

        return null;
    }

    public function ruleMatches(SalesRow $salesRow, MappingRule $rule): bool
    {
        if (! in_array($rule->match_field, self::MATCHABLE_FIELDS, true)) {
            return false;
        }

        $value = (string) ($salesRow->{$rule->match_field} ?? '');

        if ($value === '') {
            return false;
        }

        $pattern = (string) $rule->pattern;

        return match ($rule->match_operator) {
            'exact' => strcasecmp($value, $pattern) === 0,
            'starts_with' => str_starts_with(strtolower($value), strtolower($pattern)),
            'ends_with' => str_ends_with(strtolower($value), strtolower($pattern)),
            'contains' => str_contains(strtolower($value), strtolower($pattern)),
            'regex' => $this->matchesRegex($pattern, $value),
            default => false,
        };
    }

    private function matchesRegex(string $pattern, string $value): bool
    {
        set_error_handler(static fn (): bool => true);

        try {
            return preg_match($pattern, $value) === 1;
        } finally {
            restore_error_handler();
        }
    }
}
