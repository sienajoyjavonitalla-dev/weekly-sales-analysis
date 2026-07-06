<?php

namespace Tests\Feature;

use App\Domain\WeeklyAnalysis\Classification\Services\SalesRowClassifier;
use App\Models\ImportBatch;
use App\Models\MappingRule;
use App\Models\ProductCategory;
use App\Models\SalesRow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesRowClassifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_zero_amount_rows_remain_on_raw_sheet_even_when_a_mapping_rule_matches(): void
    {
        $user = User::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lyst',
            'email' => 'analyst@example.com',
            'password' => 'password',
            'role' => 'analyst',
        ]);

        $batch = ImportBatch::query()->create([
            'week_start' => '2026-02-01',
            'week_ending' => '2026-02-07',
            'status' => 'draft',
            'created_by_user_id' => $user->id,
            'source_system' => 'Traverse Global',
        ]);

        $category = ProductCategory::query()->create([
            'code' => 'rhp_miscellaneous',
            'name' => 'RHP MISCELLANEOUS',
            'report_family' => 'rhp',
            'sales_analysis_bucket' => 'rhp',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        MappingRule::query()->create([
            'name' => 'rhp_unassigned → MISCELLANEOUS',
            'product_category_id' => null,
            'source_type' => 'sales_analysis',
            'match_field' => 'item_id',
            'match_operator' => 'exact',
            'pattern' => 'MISCELLANEOUS',
            'target_bucket' => 'rhp',
            'priority' => 10,
            'is_active' => true,
        ]);

        $row = SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => 707,
            'source_bucket' => 'raw',
            'item_id' => 'MISCELLANEOUS',
            'customer_id' => 'AIK001',
            'customer_name' => 'AIKEN, ADAM',
            'invoice_number' => '144848',
            'quantity_ordered' => 0,
            'amount' => 0,
            'classification_status' => 'unmatched',
        ]);

        app(SalesRowClassifier::class)->classifyBatch($batch);

        $row->refresh();

        $this->assertSame('raw', $row->source_bucket);
        $this->assertNull($row->product_category_id);
        $this->assertNull($row->mapping_rule_id);
        $this->assertSame('matched', $row->classification_status);
    }

    public function test_non_zero_amount_rows_still_classify_into_buckets(): void
    {
        $user = User::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lyst',
            'email' => 'analyst2@example.com',
            'password' => 'password',
            'role' => 'analyst',
        ]);

        $batch = ImportBatch::query()->create([
            'week_start' => '2026-02-01',
            'week_ending' => '2026-02-07',
            'status' => 'draft',
            'created_by_user_id' => $user->id,
            'source_system' => 'Traverse Global',
        ]);

        $rule = MappingRule::query()->create([
            'name' => 'rhp_unassigned → MISCELLANEOUS',
            'product_category_id' => null,
            'source_type' => 'sales_analysis',
            'match_field' => 'item_id',
            'match_operator' => 'exact',
            'pattern' => 'MISCELLANEOUS',
            'target_bucket' => 'rhp',
            'priority' => 10,
            'is_active' => true,
        ]);

        $row = SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => 709,
            'source_bucket' => 'raw',
            'item_id' => 'MISCELLANEOUS',
            'description' => '$75 TRADE-IN BOUNTY',
            'quantity_ordered' => 1,
            'amount' => -75,
            'classification_status' => 'unmatched',
        ]);

        app(SalesRowClassifier::class)->classifyBatch($batch);

        $row->refresh();

        $this->assertSame('rhp', $row->source_bucket);
        $this->assertSame($rule->id, $row->mapping_rule_id);
    }

    public function test_classifier_uses_matching_bucket_category_when_rule_category_bucket_differs(): void
    {
        $user = User::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lyst',
            'email' => 'analyst3@example.com',
            'password' => 'password',
            'role' => 'analyst',
        ]);

        $batch = ImportBatch::query()->create([
            'week_start' => '2026-02-01',
            'week_ending' => '2026-02-07',
            'status' => 'draft',
            'created_by_user_id' => $user->id,
            'source_system' => 'Traverse Global',
        ]);

        $rhpCategory = ProductCategory::query()->create([
            'code' => 'kit_rapid_rh_l6_starter_kit_plus_fahrenheit',
            'name' => 'KIT, RAPID RH L6 STARTER KIT PLUS-FAHRENHEIT',
            'report_family' => 'rhp',
            'sales_analysis_bucket' => 'rhp',
            'sort_order' => 50,
            'is_active' => true,
        ]);

        $partsCategory = ProductCategory::query()->create([
            'code' => 'parts_tsd_kit_rapid_rh_l6_starter_kit_plus_fahrenheit',
            'name' => 'KIT, RAPID RH L6 STARTER KIT PLUS-FAHRENHEIT',
            'report_family' => 'rhp',
            'sales_analysis_bucket' => 'parts_tsd',
            'sort_order' => 50,
            'is_active' => true,
        ]);

        MappingRule::query()->create([
            'name' => 'rhp_kit_rapid_rh_l6_starter_kit_plus_fahrenheit → 880-R0002-013',
            'product_category_id' => $partsCategory->id,
            'source_type' => 'sales_analysis',
            'match_field' => 'item_id',
            'match_operator' => 'starts_with',
            'pattern' => '880-R0002-013',
            'target_bucket' => 'rhp',
            'priority' => 10,
            'is_active' => true,
        ]);

        $row = SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => 2,
            'source_bucket' => 'raw',
            'item_id' => '880-R0002-013IN',
            'amount' => 100,
            'quantity_ordered' => 1,
            'classification_status' => 'unmatched',
        ]);

        app(SalesRowClassifier::class)->classifyBatch($batch);

        $row->refresh();

        $this->assertSame('rhp', $row->source_bucket);
        $this->assertSame($rhpCategory->id, $row->product_category_id);
    }
}
