<?php

namespace Tests\Feature;

use App\Domain\WeeklyAnalysis\Exports\Services\WeeklyReportExportService;
use App\Models\GeneratedReport;
use App\Models\ImportBatch;
use App\Models\MappingRule;
use App\Models\ProductCategory;
use App\Models\ReconciliationResult;
use App\Models\SalesRow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class GeneratedReportRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_a_category_refreshes_the_meter_report_and_reconciliation(): void
    {
        [$admin, $batch, $category] = $this->createGeneratedBatch();

        $this->assertSame(1246.0, $this->meterSales('RHP L6 Kits'));

        $this->actingAs($admin)
            ->patchJson("/api/product-categories/{$category->id}", ['weekly_meter_row_label' => 'RHP 4.0 Kits'])
            ->assertOk()
            ->assertJsonPath('meta.reports_refreshed', 1);

        $this->assertSame(0.0, $this->meterSales('RHP L6 Kits'));
        $this->assertSame(1246.0, $this->meterSales('RHP 4.0 Kits'));

        // The stored workbook is rebuilt too, not only the live page data.
        $meter = GeneratedReport::query()
            ->where('import_batch_id', $batch->id)
            ->where('report_type', 'weekly_meter_report')
            ->firstOrFail();
        $sheet = IOFactory::load(storage_path('app/private/'.$meter->storage_path))->getActiveSheet();
        $labels = collect(range(1, 60))->map(fn (int $row) => $sheet->getCell('A'.$row)->getValue());

        $this->assertTrue($labels->contains('RHP 4.0 Kits'));
    }

    public function test_deleting_a_mapping_rule_removes_its_rows_from_the_sales_analysis_total(): void
    {
        [$admin, $batch] = $this->createGeneratedBatch();
        $rule = MappingRule::query()->firstOrFail();

        $this->assertEquals(1246.0, ReconciliationResult::query()->where('import_batch_id', $batch->id)->value('sales_analysis_total'));

        $this->actingAs($admin)
            ->deleteJson("/api/mapping-rules/{$rule->id}")
            ->assertOk()
            ->assertJsonPath('meta.reports_refreshed', 1);

        $this->assertEquals(0.0, ReconciliationResult::query()->where('import_batch_id', $batch->id)->value('sales_analysis_total'));
        $this->assertSame('unmatched', SalesRow::query()->where('import_batch_id', $batch->id)->value('classification_status'));
    }

    public function test_saving_without_changes_or_skipping_does_not_refresh_reports(): void
    {
        [$admin, , $category] = $this->createGeneratedBatch();

        $this->actingAs($admin)
            ->patchJson("/api/product-categories/{$category->id}", ['weekly_meter_row_label' => 'RHP L6 Kits'])
            ->assertOk()
            ->assertJsonPath('meta.reports_refreshed', 0);

        $this->actingAs($admin)
            ->postJson('/api/mapping-rules', [
                'name' => 'Another rule',
                'product_category_id' => $category->id,
                'source_type' => 'sales_analysis',
                'match_field' => 'item_id',
                'match_operator' => 'exact',
                'pattern' => 'SOMETHING-ELSE',
                'target_bucket' => 'rhp',
                'priority' => 20,
                'is_active' => true,
                'skip_report_refresh' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('meta.reports_refreshed', 0);
    }

    private function meterSales(string $label): float
    {
        $report = $this->getJson('/api/weekly-meter-report?year=2026&month=4')->assertOk()->json('data');
        $row = collect($report['rows'])->firstWhere('label', $label);

        return (float) ($row['weeks'][0]['sales'] ?? 0);
    }

    /**
     * @return array{0: User, 1: ImportBatch, 2: ProductCategory}
     */
    private function createGeneratedBatch(): array
    {
        $admin = User::query()->create([
            'first_name' => 'Ad',
            'last_name' => 'Min',
            'email' => 'admin-refresh@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        $category = ProductCategory::query()->create([
            'code' => 'kit_rapid_rh_l6_starter_kit_plus_fahrenheit',
            'name' => 'KIT, RAPID RH L6 STARTER KIT PLUS-FAHRENHEIT',
            'report_family' => 'rhp',
            'sales_analysis_bucket' => 'rhp',
            'weekly_meter_row_label' => 'RHP L6 Kits',
            'sort_order' => 30,
            'is_active' => true,
        ]);

        MappingRule::query()->create([
            'name' => 'L6 kit by item id',
            'product_category_id' => $category->id,
            'source_type' => 'sales_analysis',
            'match_field' => 'item_id',
            'match_operator' => 'exact',
            'pattern' => '880-R0002-012',
            'target_bucket' => 'rhp',
            'priority' => 10,
            'is_active' => true,
        ]);

        $batch = ImportBatch::query()->create([
            'week_start' => '2026-03-28',
            'week_ending' => '2026-04-03',
            'status' => 'draft',
            'created_by_user_id' => $admin->id,
            'source_system' => 'Traverse Global',
        ]);

        SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'product_category_id' => null,
            'source_sheet' => 'Sheet',
            'source_row_number' => 1,
            'source_bucket' => 'raw',
            'item_id' => '880-R0002-012',
            'description' => $category->name,
            'quantity_ordered' => 2,
            'amount' => 1246.00,
            'classification_status' => 'unmatched',
        ]);

        app(WeeklyReportExportService::class)->exportAll($batch, $admin->id);

        return [$admin, $batch, $category];
    }
}
