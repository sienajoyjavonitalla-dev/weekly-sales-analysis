<?php

namespace Tests\Unit;

use App\Domain\WeeklyAnalysis\Classification\Services\MappingRuleMatcher;
use App\Models\MappingRule;
use App\Models\SalesRow;
use PHPUnit\Framework\TestCase;

class MappingRuleMatcherTest extends TestCase
{
    public function test_invalid_regex_rule_does_not_match_or_throw(): void
    {
        $matcher = new MappingRuleMatcher();
        $salesRow = new SalesRow(['item_id' => '880-R4100-005']);
        $rule = new MappingRule([
            'match_field' => 'item_id',
            'match_operator' => 'regex',
            'pattern' => '/^(880-R/',
        ]);

        $this->assertFalse($matcher->ruleMatches($salesRow, $rule));
    }

    public function test_starts_with_rule_matches_case_insensitively(): void
    {
        $matcher = new MappingRuleMatcher();
        $salesRow = new SalesRow(['item_id' => '880-R4100-005']);
        $rule = new MappingRule([
            'match_field' => 'item_id',
            'match_operator' => 'starts_with',
            'pattern' => '880-r',
        ]);

        $this->assertTrue($matcher->ruleMatches($salesRow, $rule));
    }
}
