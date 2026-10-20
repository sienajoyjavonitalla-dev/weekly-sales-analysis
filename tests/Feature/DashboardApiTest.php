<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ProductCategory;
use App\Models\ReconciliationResult;
use App\Models\SalesRow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_fetch_dashboard(): void
    {
        $this->getJson('/api/dashboard?year=2026&month=4')->assertUnauthorized();
    }

    public function test_empty_month_returns_zeroed_payload(): void
    {
        $user = $this->createAnalyst();

        $this->actingAs($user)
            ->getJson('/api/dashboard?year=2026&month=1')
            ->assertOk()
            ->assertJsonPath('data.title', 'January 2026')
            ->assertJsonPath('data.sales_analysis.mtd_total', 0)
            ->assertJsonPath('data.sales_analysis.change_percent', null)
            ->assertJsonPath('data.sales_analysis.top_categories', [])
            ->assertJsonPath('data.meter.mtd_sales', 0)
            ->assertJsonPath('data.health.weeks_with_data', 0)
            ->assertJsonPath('data.weeks.0.has_data', false);
    }

    public function test_dashboard_aggregates_sales_analysis_and_meter_data(): void
    {
        $user = $this->createAnalyst();
        $kits = $this->createCategory('kit_rapid_rh_l6_starter_kit_plus_fahrenheit', 'RHP L6 STARTER KIT', 'rhp', 'RHP L6 Kits');
        $parts = $this->createCategory('parts_tsd_misc', 'PARTS MISC', 'parts_tsd', null);

        // Previous month, used for the month-over-month comparison.
        $march = $this->createBatch($user, '2026-03-27');
        $this->addRow($march, $kits, 'rhp', 'Acme', 5, 2000.00, 1);

        // Sales Analysis workbooks are month-to-date: week 2 includes everything from week 1.
        $weekOne = $this->createBatch($user, '2026-04-03');
        $this->addRow($weekOne, $kits, 'rhp', 'Acme', 2, 1000.00, 1);

        $weekTwo = $this->createBatch($user, '2026-04-10');
        $this->addRow($weekTwo, $kits, 'rhp', 'Acme', 6, 3000.00, 1);
        $this->addRow($weekTwo, $parts, 'parts_tsd', 'Beta Co', 1, 500.00, 2);
        $this->addRow($weekTwo, $parts, 'parts_tsd', 'Beta Co', 1, 75.00, 3, 'unmatched');

        ReconciliationResult::query()->create([
            'import_batch_id' => $weekTwo->id,
            'sales_analysis_total' => 3500.00,
            'income_statement_total' => 3500.00,
            'marketplace_fee_total' => 0,
            'adjusted_income_statement_total' => 3500.00,
            'difference' => 0,
            'is_balanced' => true,
            'tolerance' => 0.01,
        ]);

        $data = $this->actingAs($user)
            ->getJson('/api/dashboard?year=2026&month=4')
            ->assertOk()
            ->json('data');

        $this->assertSame('April 2026', $data['title']);

        $this->assertEqualsWithDelta(3500.0, $data['sales_analysis']['mtd_total'], 0.001);
        $this->assertEqualsWithDelta(2000.0, $data['sales_analysis']['previous_mtd_total'], 0.001);
        $this->assertEqualsWithDelta(75.0, $data['sales_analysis']['change_percent'], 0.001);
        $this->assertSame($weekTwo->id, $data['sales_analysis']['latest_import_batch_id']);

        $buckets = collect($data['sales_analysis']['buckets'])->keyBy('bucket');
        $this->assertEqualsWithDelta(3000.0, $buckets['rhp']['amount'], 0.001);
        $this->assertEqualsWithDelta(500.0, $buckets['parts_tsd']['amount'], 0.001);
        $this->assertSame('PARTS & TSD', $buckets['parts_tsd']['label']);

        $this->assertSame('RHP L6 STARTER KIT', $data['sales_analysis']['top_categories'][0]['name']);
        $this->assertSame('Acme', $data['sales_analysis']['top_customers'][0]['name']);

        // Weekly figures are the difference between consecutive month-to-date totals.
        $this->assertEqualsWithDelta(1000.0, $data['weeks'][0]['sales_analysis_weekly'], 0.001);
        $this->assertEqualsWithDelta(2500.0, $data['weeks'][1]['sales_analysis_weekly'], 0.001);
        $this->assertEqualsWithDelta(3500.0, $data['weeks'][1]['sales_analysis_mtd'], 0.001);
        $this->assertSame(1, $data['weeks'][1]['unmatched_rows']);
        $this->assertTrue($data['weeks'][1]['reconciliation']['is_balanced']);
        $this->assertNull($data['weeks'][0]['reconciliation']);
        $this->assertFalse($data['weeks'][2]['has_data']);

        $this->assertEqualsWithDelta(3000.0, $data['meter']['mtd_sales'], 0.001);
        $this->assertEqualsWithDelta(6.0, $data['meter']['mtd_units'], 0.001);
        $this->assertEqualsWithDelta(1000.0, $data['weeks'][0]['meter_sales'], 0.001);
        $this->assertEqualsWithDelta(2000.0, $data['weeks'][1]['meter_sales'], 0.001);
        $this->assertSame('RHP L6 Kits', $data['meter']['top_models_by_sales'][0]['label']);
        $this->assertEqualsWithDelta(50.0, $data['meter']['sales_change_percent'], 0.001);

        $this->assertSame(2, $data['health']['weeks_with_data']);
        $this->assertSame(1, $data['health']['unmatched_rows']);
        $this->assertSame(1, $data['health']['reconciled_weeks']);
        $this->assertSame(1, $data['health']['balanced_weeks']);
        $this->assertSame(0, $data['health']['unbalanced_weeks']);
    }

    public function test_dashboard_validates_month(): void
    {
        $this->actingAs($this->createAnalyst())
            ->getJson('/api/dashboard?year=2026&month=13')
            ->assertUnprocessable();
    }

    private function createAnalyst(): User
    {
        return User::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lyst',
            'email' => 'analyst-dashboard@example.com',
            'password' => 'password',
            'role' => 'analyst',
        ]);
    }

    private function createCategory(string $code, string $name, string $bucket, ?string $meterLabel): ProductCategory
    {
        return ProductCategory::query()->create([
            'code' => $code,
            'name' => $name,
            'report_family' => $bucket,
            'sales_analysis_bucket' => $bucket,
            'weekly_meter_row_label' => $meterLabel,
            'sort_order' => 1,
            'quantity_multiplier' => 1,
            'is_active' => true,
        ]);
    }

    private function createBatch(User $user, string $weekEnding): ImportBatch
    {
        return ImportBatch::query()->create([
            'week_start' => date('Y-m-d', strtotime($weekEnding.' -6 days')),
            'week_ending' => $weekEnding,
            'status' => 'draft',
            'created_by_user_id' => $user->id,
            'source_system' => 'Traverse Global '.$weekEnding,
        ]);
    }

    private function addRow(
        ImportBatch $batch,
        ProductCategory $category,
        string $bucket,
        string $customer,
        float $quantity,
        float $amount,
        int $sourceRow,
        string $status = 'matched',
    ): SalesRow {
        return SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'product_category_id' => $category->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => $sourceRow,
            'source_bucket' => $bucket,
            'item_id' => 'ITEM-'.$sourceRow,
            'description' => $category->name,
            'customer_name' => $customer,
            'quantity_ordered' => $quantity,
            'amount' => $amount,
            'classification_status' => $status,
        ]);
    }
}
