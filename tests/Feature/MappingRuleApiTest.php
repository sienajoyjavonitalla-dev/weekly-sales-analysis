<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\MappingRule;
use App\Models\ProductCategory;
use App\Models\SalesRow;
use App\Models\SalesRowStatePlacement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MappingRuleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_create_duplicate_active_mapping_rule_signature_between_rhp_and_parts_tsd(): void
    {
        $user = $this->createAnalyst();

        MappingRule::query()->create([
            'name' => 'rhp_unassigned → 694-R0003-002',
            'source_type' => 'sales_analysis',
            'match_field' => 'item_id',
            'match_operator' => 'exact',
            'pattern' => '694-R0003-002',
            'target_bucket' => 'rhp',
            'priority' => 14,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->postJson('/api/mapping-rules', [
            'name' => 'duplicate rule',
            'source_type' => 'sales_analysis',
            'match_field' => 'item_id',
            'match_operator' => 'exact',
            'pattern' => '694-R0003-002',
            'target_bucket' => 'parts_tsd',
            'priority' => 204,
            'is_active' => true,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['pattern']);
    }

    public function test_can_create_state_mapping_rule_with_same_signature_as_rhp(): void
    {
        $user = $this->createAdmin();

        MappingRule::query()->create([
            'name' => 'rhp → 890-00080-001',
            'source_type' => 'sales_analysis',
            'match_field' => 'item_id',
            'match_operator' => 'exact',
            'pattern' => '890-00080-001',
            'target_bucket' => 'rhp',
            'priority' => 27,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->postJson('/api/mapping-rules', [
            'name' => 'state → 890-00080-001',
            'source_type' => 'sales_analysis',
            'match_field' => 'item_id',
            'match_operator' => 'exact',
            'pattern' => '890-00080-001',
            'target_bucket' => 'state',
            'priority' => 32,
            'is_active' => true,
        ]);

        $response->assertCreated();
    }

    public function test_cannot_create_duplicate_active_mapping_rule_signature_within_same_bucket(): void
    {
        $user = $this->createAnalyst();

        MappingRule::query()->create([
            'name' => 'state → 890-00080-001',
            'source_type' => 'sales_analysis',
            'match_field' => 'item_id',
            'match_operator' => 'exact',
            'pattern' => '890-00080-001',
            'target_bucket' => 'state',
            'priority' => 32,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->postJson('/api/mapping-rules', [
            'name' => 'duplicate state rule',
            'source_type' => 'sales_analysis',
            'match_field' => 'item_id',
            'match_operator' => 'exact',
            'pattern' => '890-00080-001',
            'target_bucket' => 'state',
            'priority' => 40,
            'is_active' => true,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['pattern']);
    }

    public function test_classified_rhp_row_also_creates_state_placement_when_state_rule_exists(): void
    {
        $batch = ImportBatch::query()->create([
            'week_start' => '2026-02-01',
            'week_ending' => '2026-02-07',
            'status' => 'draft',
            'created_by_user_id' => null,
            'source_system' => 'Traverse Global',
        ]);

        MappingRule::query()->create([
            'name' => 'rhp → 890-00080-001',
            'source_type' => 'sales_analysis',
            'match_field' => 'item_id',
            'match_operator' => 'exact',
            'pattern' => '890-00080-001',
            'target_bucket' => 'rhp',
            'priority' => 10,
            'is_active' => true,
        ]);

        MappingRule::query()->create([
            'name' => 'state → 890-00080-001',
            'source_type' => 'sales_analysis',
            'match_field' => 'item_id',
            'match_operator' => 'exact',
            'pattern' => '890-00080-001',
            'target_bucket' => 'state',
            'priority' => 20,
            'is_active' => true,
        ]);

        $stateRule = MappingRule::query()->where('pattern', '890-00080-001')->where('target_bucket', 'state')->first();

        $row = SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => 2,
            'source_bucket' => 'raw',
            'item_id' => '890-00080-001',
            'amount' => 189,
            'quantity_ordered' => 1,
            'classification_status' => 'unmatched',
        ]);

        app(\App\Domain\WeeklyAnalysis\Classification\Services\SalesRowClassifier::class)->classifyBatch($batch);

        $row->refresh();

        $this->assertSame('rhp', $row->source_bucket);

        $placement = SalesRowStatePlacement::query()
            ->where('sales_row_id', $row->id)
            ->first();

        $this->assertNotNull($placement);
        $this->assertSame($stateRule->id, $placement->mapping_rule_id);
    }

    public function test_mapping_rule_seeder_keeps_single_active_rhp_rule_for_shared_item(): void
    {
        $this->seed(\Database\Seeders\ProductCategorySeeder::class);

        $rhpAndPartsRules = MappingRule::query()
            ->where('source_type', 'sales_analysis')
            ->where('match_field', 'item_id')
            ->where('match_operator', 'exact')
            ->where('pattern', '694-R0003-002')
            ->whereIn('target_bucket', ['rhp', 'parts_tsd'])
            ->where('is_active', true)
            ->get();

        $this->assertCount(1, $rhpAndPartsRules);
        $this->assertSame('rhp', $rhpAndPartsRules->first()->target_bucket);
        $this->assertSame('rhp_sales_analysis_template', $rhpAndPartsRules->first()->metadata['source'] ?? null);
    }

    public function test_cannot_assign_parts_tsd_category_to_rhp_mapping_rule(): void
    {
        $user = $this->createAdmin();

        $partsCategory = ProductCategory::query()->create([
            'code' => 'parts_tsd_kit_rapid_rh_l6_starter_kit_plus_fahrenheit',
            'name' => 'KIT, RAPID RH L6 STARTER KIT PLUS-FAHRENHEIT',
            'report_family' => 'rhp',
            'sales_analysis_bucket' => 'parts_tsd',
            'sort_order' => 50,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->postJson('/api/mapping-rules', [
            'name' => 'bad category bucket rule',
            'product_category_id' => $partsCategory->id,
            'source_type' => 'sales_analysis',
            'match_field' => 'item_id',
            'match_operator' => 'starts_with',
            'pattern' => '880-R0002-013',
            'target_bucket' => 'rhp',
            'priority' => 300,
            'is_active' => true,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_category_id']);
    }

    private function createAnalyst(): User
    {
        return User::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lyst',
            'email' => 'analyst@example.com',
            'password' => 'password',
            'role' => 'analyst',
        ]);
    }

    private function createAdmin(): User
    {
        return User::query()->create([
            'first_name' => 'Ada',
            'last_name' => 'Min',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);
    }
}
