<?php

namespace Tests\Feature;

use App\Models\GeneratedReport;
use App\Models\ImportBatch;
use App\Models\ProductCategory;
use App\Models\ReconciliationResult;
use App\Models\SalesRow;
use App\Models\UploadedFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_requires_authentication(): void
    {
        $this->getJson('/api/report-calendar?year=2026&month=4')->assertUnauthorized();
    }

    public function test_calendar_returns_report_totals_for_a_week_with_generated_reports(): void
    {
        $user = $this->createAnalyst();
        $category = $this->createStarterKitCategory();
        $batch = $this->createBatch($user, '2026-04-03');
        $this->addSalesRow($batch, $category, 2, 1246.00);

        ReconciliationResult::query()->create([
            'import_batch_id' => $batch->id,
            'sales_analysis_total' => 425834.37,
        ]);
        $salesAnalysis = $this->createReport($batch, 'sales_analysis');
        $meter = $this->createReport($batch, 'weekly_meter_report');
        $amazonFees = $this->createReport($batch, 'amazon_fees');

        $response = $this->actingAs($user)
            ->getJson('/api/report-calendar?year=2026&month=4')
            ->assertOk()
            ->assertJsonCount(1, 'data.weeks')
            ->assertJsonPath('data.weeks.0.id', $batch->id)
            ->assertJsonPath('data.weeks.0.week_start', '2026-03-28')
            ->assertJsonPath('data.weeks.0.week_ending', '2026-04-03')
            ->assertJsonPath('data.weeks.0.meter_year', 2026)
            ->assertJsonPath('data.weeks.0.meter_month', 4)
            ->assertJsonCount(3, 'data.weeks.0.reports');

        $reports = collect($response->json('data.weeks.0.reports'))->keyBy('report_type');

        $this->assertSame($salesAnalysis->id, $reports['sales_analysis']['id']);
        $this->assertEquals(425834.37, $reports['sales_analysis']['total']);
        $this->assertSame($meter->id, $reports['weekly_meter_report']['id']);
        $this->assertEquals(1246.0, $reports['weekly_meter_report']['total']);
        $this->assertSame($amazonFees->id, $reports['amazon_fees']['id']);
        $this->assertEquals(0.0, $reports['amazon_fees']['total']);
    }

    public function test_calendar_lists_uploaded_weeks_without_generated_reports(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createBatch($user, '2026-04-10');

        $this->actingAs($user)
            ->getJson('/api/report-calendar?year=2026&month=4')
            ->assertOk()
            ->assertJsonPath('data.weeks.0.id', $batch->id)
            ->assertJsonPath('data.weeks.0.reports', []);
    }

    public function test_calendar_diagnostics_explain_which_batches_are_left_out(): void
    {
        $user = $this->createAnalyst();
        $shown = $this->createBatch($user, '2026-04-10');
        $hidden = $this->createBatch($user, '2026-04-17', withUpload: false);

        $response = $this->actingAs($user)
            ->getJson('/api/report-calendar?year=2026&month=4')
            ->assertOk()
            ->assertJsonPath('data.diagnostics.weeks_returned', 1)
            ->assertJsonPath('data.diagnostics.total_batches', 2)
            ->assertJsonPath('data.diagnostics.latest_batch.id', $hidden->id)
            ->assertJsonCount(2, 'data.diagnostics.batches_in_window');

        $batches = collect($response->json('data.diagnostics.batches_in_window'))->keyBy('id');

        $this->assertTrue($batches[$shown->id]['shown_in_calendar']);
        $this->assertFalse($batches[$hidden->id]['shown_in_calendar']);
        $this->assertSame(0, $batches[$hidden->id]['uploaded_files']);
        $this->assertNotEmpty($response->json('data.diagnostics.database'));
    }

    public function test_calendar_ignores_batches_without_uploads(): void
    {
        $user = $this->createAnalyst();
        $this->createBatch($user, '2026-04-10', withUpload: false);

        $this->actingAs($user)
            ->getJson('/api/report-calendar?year=2026&month=4')
            ->assertOk()
            ->assertJsonCount(0, 'data.weeks');
    }

    public function test_week_spanning_months_uses_week_ending_month_for_meter_report(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createBatch($user, '2026-05-01');

        // Visible from both the starting and ending month.
        foreach ([4, 5] as $month) {
            $this->actingAs($user)
                ->getJson("/api/report-calendar?year=2026&month={$month}")
                ->assertOk()
                ->assertJsonPath('data.weeks.0.id', $batch->id)
                ->assertJsonPath('data.weeks.0.meter_year', 2026)
                ->assertJsonPath('data.weeks.0.meter_month', 5);
        }
    }

    private function createAnalyst(): User
    {
        return User::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lyst',
            'email' => 'analyst-calendar@example.com',
            'password' => 'password',
            'role' => 'analyst',
        ]);
    }

    private function createStarterKitCategory(): ProductCategory
    {
        return ProductCategory::query()->create([
            'code' => 'kit_rapid_rh_l6_starter_kit_plus_fahrenheit',
            'name' => 'KIT, RAPID RH L6 STARTER KIT PLUS-FAHRENHEIT',
            'report_family' => 'rhp',
            'sales_analysis_bucket' => 'rhp',
            'weekly_meter_row_label' => 'RHP L6 Kits',
            'sort_order' => 30,
            'is_active' => true,
        ]);
    }

    private function createBatch(User $user, string $weekEnding, bool $withUpload = true): ImportBatch
    {
        $batch = ImportBatch::query()->create([
            'week_start' => date('Y-m-d', strtotime($weekEnding.' -6 days')),
            'week_ending' => $weekEnding,
            'status' => 'draft',
            'created_by_user_id' => $user->id,
            'source_system' => 'Traverse Global '.$weekEnding,
        ]);

        if ($withUpload) {
            UploadedFile::query()->create([
                'import_batch_id' => $batch->id,
                'file_type' => 'sales_analysis',
                'original_name' => 'sales.xlsx',
                'storage_path' => 'uploads/sales.xlsx',
                'sha256_checksum' => str_repeat('a', 64),
                'status' => 'imported',
            ]);
        }

        return $batch;
    }

    private function addSalesRow(ImportBatch $batch, ProductCategory $category, float $quantity, float $amount): SalesRow
    {
        return SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'product_category_id' => $category->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => 1,
            'source_bucket' => 'rhp',
            'item_id' => '880-R0002-012',
            'description' => $category->name,
            'quantity_ordered' => $quantity,
            'amount' => $amount,
            'classification_status' => 'matched',
        ]);
    }

    private function createReport(ImportBatch $batch, string $reportType): GeneratedReport
    {
        return GeneratedReport::query()->create([
            'import_batch_id' => $batch->id,
            'report_type' => $reportType,
            'status' => 'completed',
            'file_name' => "{$reportType}.xlsx",
            'storage_path' => "generated-reports/{$reportType}.xlsx",
        ]);
    }
}
