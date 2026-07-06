<?php

namespace Tests\Unit;

use App\Domain\WeeklyAnalysis\Classification\Support\SalesAnalysisBucketPolicy;
use PHPUnit\Framework\TestCase;

class SalesAnalysisBucketPolicyTest extends TestCase
{
    public function test_state_rules_can_overlap_rhp_or_parts_tsd_rules(): void
    {
        $this->assertFalse(SalesAnalysisBucketPolicy::mappingRulesConflict('rhp', 'state'));
        $this->assertFalse(SalesAnalysisBucketPolicy::mappingRulesConflict('state', 'parts_tsd'));
    }

    public function test_rhp_and_parts_tsd_rules_cannot_overlap(): void
    {
        $this->assertTrue(SalesAnalysisBucketPolicy::mappingRulesConflict('rhp', 'parts_tsd'));
    }

    public function test_same_bucket_rules_cannot_overlap(): void
    {
        $this->assertTrue(SalesAnalysisBucketPolicy::mappingRulesConflict('state', 'state'));
    }
}
