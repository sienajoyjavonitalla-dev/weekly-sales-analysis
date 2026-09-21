<?php

namespace Tests\Feature;

use App\Domain\WeeklyAnalysis\Exports\Services\WeeklyMeterReportExporter;
use App\Domain\WeeklyAnalysis\Reports\WeeklyMeterReportBuilder;
use App\Models\ImportBatch;
use App\Models\ProductCategory;
use App\Models\SalesRow;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class WeeklyMeterReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_upload_types_no_longer_include_weekly_meter_report(): void
    {
        $this->assertSame([
            'sales_analysis',
            'income_statement',
            'total_sales_report',
            'open_orders',
            'ptd_orders',
        ], config('weekly-analysis.uploads.required_file_types'));
    }

    public function test_week_one_uses_sales_analysis_totals_and_later_weeks_subtract_displayed_values(): void
    {
        $user = $this->createAnalyst();
        $category = $this->createStarterKitCategory();

        $this->createWeekSales($user, '2026-04-03', $category, 2, 1246.00, 1);
        $this->createWeekSales($user, '2026-04-10', $category, 18, 12549.00, 2);
        $this->createWeekSales($user, '2026-04-17', $category, 26, 17533.00, 3);

        $report = app(WeeklyMeterReportBuilder::class)->build(2026, 4, CarbonImmutable::parse('2026-09-21'));
        $row = collect($report['rows'])->firstWhere('label', 'RHP L6 Kits');

        $this->assertSame('April 2026 Handmeter/RHP Shipments', $report['title']);
        $this->assertSame([2025, 2026], $report['years']);
        $this->assertSame(range(1, 9), $report['available_months']);
        $this->assertTrue($report['weeks'][2]['has_data']);

        $this->assertSame(2.0, $row['weeks'][0]['units']);
        $this->assertSame(1246.0, $row['weeks'][0]['sales']);
        $this->assertSame(16.0, $row['weeks'][1]['units']);
        $this->assertSame(11303.0, $row['weeks'][1]['sales']);
        $this->assertSame(8.0, $row['weeks'][2]['units']);
        $this->assertSame(4984.0, $row['weeks'][2]['sales']);
        $this->assertSame(26.0, $row['mtd_units']);
        $this->assertSame(17533.0, $row['mtd_sales']);
    }

    public function test_empty_month_returns_blank_week_columns(): void
    {
        $report = app(WeeklyMeterReportBuilder::class)->build(2026, 1, CarbonImmutable::parse('2026-09-21'));
        $row = collect($report['rows'])->firstWhere('label', 'RHP L6 Kits');

        $this->assertFalse($report['weeks'][0]['has_data']);
        $this->assertNull($row['weeks'][0]['units']);
        $this->assertNull($row['weeks'][0]['sales']);
        $this->assertSame(0.0, $row['mtd_sales']);
    }

    public function test_analyst_can_fetch_weekly_meter_report_payload(): void
    {
        $user = $this->createAnalyst();
        $category = $this->createStarterKitCategory();
        $this->createWeekSales($user, '2026-04-03', $category, 2, 1246.00, 1);

        $this->getJson('/api/weekly-meter-report?year=2026&month=4')->assertUnauthorized();

        $this->actingAs($user)
            ->getJson('/api/weekly-meter-report?year=2026&month=4')
            ->assertOk()
            ->assertJsonPath('data.title', 'April 2026 Handmeter/RHP Shipments')
            ->assertJsonPath('data.weeks.0.range', '04/01-04/03')
            ->assertJsonPath('data.weeks.0.has_data', true);
    }

    public function test_exporter_creates_workbook_without_an_uploaded_template(): void
    {
        $user = $this->createAnalyst();
        $category = $this->createStarterKitCategory();
        $batch = $this->createWeekSales($user, '2026-04-17', $category, 26, 17533.00, 3);

        $generated = app(WeeklyMeterReportExporter::class)->export($batch, $user->id);
        $path = storage_path('app/private/'.$generated->storage_path);

        $this->assertSame('completed', $generated->status);
        $this->assertFileExists($path);

        $sheet = IOFactory::load($path)->getActiveSheet();
        $this->assertSame('April 2026 Handmeter/RHP Shipments', $sheet->getCell('A1')->getValue());
        $this->assertSame('RHP L6 Kits', $sheet->getCell('A7')->getValue());
        $this->assertSame(26.0, (float) $sheet->getCell('F7')->getCalculatedValue());
        $this->assertSame(17533.0, (float) $sheet->getCell('G7')->getCalculatedValue());
    }

    private function createAnalyst(): User
    {
        return User::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lyst',
            'email' => 'analyst-meter@example.com',
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

    private function createWeekSales(
        User $user,
        string $weekEnding,
        ProductCategory $category,
        float $quantity,
        float $amount,
        int $sourceRow,
    ): ImportBatch {
        $batch = ImportBatch::query()->create([
            'week_start' => date('Y-m-d', strtotime($weekEnding.' -6 days')),
            'week_ending' => $weekEnding,
            'status' => 'draft',
            'created_by_user_id' => $user->id,
            'source_system' => 'Traverse Global '.$weekEnding,
        ]);

        SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'product_category_id' => $category->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => $sourceRow,
            'source_bucket' => 'rhp',
            'item_id' => '880-R0002-012',
            'description' => 'KIT, RAPID RH L6 STARTER KIT PLUS-FAHRENHEIT',
            'quantity_ordered' => $quantity,
            'amount' => $amount,
            'classification_status' => 'matched',
        ]);

        return $batch;
    }
}
