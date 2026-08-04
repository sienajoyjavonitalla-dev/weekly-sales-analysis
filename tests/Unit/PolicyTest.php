<?php

namespace Tests\Unit;

use App\Models\GeneratedReport;
use App\Models\ImportBatch;
use App\Models\MappingRule;
use App\Models\User;
use App\Policies\GeneratedReportPolicy;
use App\Policies\ImportBatchPolicy;
use App\Policies\MappingRulePolicy;
use App\Policies\UserPolicy;
use PHPUnit\Framework\TestCase;

class PolicyTest extends TestCase
{
    public function test_admin_can_manage_mapping_rules(): void
    {
        $policy = new MappingRulePolicy();
        $admin = new User(['role' => 'admin']);
        $rule = new MappingRule();

        $this->assertTrue($policy->create($admin));
        $this->assertTrue($policy->update($admin, $rule));
        $this->assertTrue($policy->delete($admin, $rule));
    }

    public function test_analyst_cannot_manage_mapping_rules(): void
    {
        $policy = new MappingRulePolicy();
        $analyst = new User(['role' => 'analyst']);
        $rule = new MappingRule();

        $this->assertFalse($policy->create($analyst));
        $this->assertFalse($policy->update($analyst, $rule));
        $this->assertFalse($policy->delete($analyst, $rule));
    }

    public function test_analyst_can_update_own_draft_batch(): void
    {
        $policy = new ImportBatchPolicy();
        $analyst = new User(['role' => 'analyst']);
        $analyst->id = 10;
        $batch = new ImportBatch([
            'created_by_user_id' => 10,
            'status' => 'draft',
        ]);

        $this->assertTrue($policy->update($analyst, $batch));
    }

    public function test_only_completed_reports_can_be_downloaded(): void
    {
        $policy = new GeneratedReportPolicy();
        $analyst = new User(['role' => 'analyst']);

        $this->assertTrue($policy->download($analyst, new GeneratedReport(['status' => 'completed'])));
        $this->assertFalse($policy->download($analyst, new GeneratedReport(['status' => 'failed'])));
    }

    public function test_admin_can_manage_users(): void
    {
        $policy = new UserPolicy();
        $admin = new User(['role' => 'admin']);
        $admin->id = 1;
        $model = new User(['role' => 'analyst']);
        $model->id = 2;

        $this->assertTrue($policy->viewAny($admin));
        $this->assertTrue($policy->create($admin));
        $this->assertTrue($policy->update($admin, $model));
        $this->assertTrue($policy->delete($admin, $model));
        $this->assertFalse($policy->delete($admin, $admin));
    }

    public function test_analyst_cannot_manage_users(): void
    {
        $policy = new UserPolicy();
        $analyst = new User(['role' => 'analyst']);
        $model = new User(['role' => 'analyst']);

        $this->assertFalse($policy->viewAny($analyst));
        $this->assertFalse($policy->create($analyst));
        $this->assertFalse($policy->update($analyst, $model));
        $this->assertFalse($policy->delete($analyst, $model));
    }
}
