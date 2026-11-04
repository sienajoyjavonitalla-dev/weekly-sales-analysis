<?php

namespace Tests\Feature;

use App\Domain\WeeklyAnalysis\Exports\Services\WeeklyMeterReportExporter;
use App\Domain\WeeklyAnalysis\Reports\WeeklyMeterModelCatalog;
use App\Domain\WeeklyAnalysis\Reports\WeeklyMeterReportBuilder;
use App\Models\ImportBatch;
use App\Models\ImportBatchFeeAdjustment;
use App\Models\ProductCategory;
use App\Models\SalesRow;
use App\Models\UploadedFile;
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
            'amazon_fees',
            'walmart_fees',
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
        $this->assertNull($report['totals']['additional_fees_percent']);
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

    public function test_year_workbook_has_a_sheet_for_every_month_including_empty_ones(): void
    {
        $user = $this->createAnalyst();
        $category = $this->createStarterKitCategory();
        $this->createWeekSales($user, '2026-02-06', $category, 3, 900.00, 1);
        $this->createWeekSales($user, '2026-04-17', $category, 26, 17533.00, 1);

        $spreadsheet = app(WeeklyMeterReportExporter::class)
            ->exportYear(2026, CarbonImmutable::parse('2026-09-21'));

        $this->assertNotNull($spreadsheet);
        $this->assertSame(12, $spreadsheet->getSheetCount());
        $this->assertSame('January 2026', $spreadsheet->getSheetNames()[0]);
        $this->assertSame('December 2026', $spreadsheet->getSheetNames()[11]);
        $this->assertSame('RHP L6 Kits', $spreadsheet->getSheetByName('April 2026')->getCell('A7')->getValue());
        $this->assertNull($spreadsheet->getSheetByName('March 2026')->getCell('G7')->getValue());
        $this->assertSame(17533.0, (float) $spreadsheet->getSheetByName('April 2026')->getCell('G7')->getCalculatedValue());
    }

    public function test_year_workbook_is_null_when_year_has_no_data(): void
    {
        $this->assertNull(app(WeeklyMeterReportExporter::class)->exportYear(2025, CarbonImmutable::parse('2026-09-21')));
    }

    public function test_analyst_can_download_year_report_and_empty_year_is_rejected(): void
    {
        $user = $this->createAnalyst();
        $category = $this->createStarterKitCategory();
        $this->createWeekSales($user, '2026-04-17', $category, 26, 17533.00, 1);

        $this->getJson('/api/weekly-meter-report/download?year=2026')->assertUnauthorized();

        $response = $this->actingAs($user)->get('/api/weekly-meter-report/download?year=2026');
        $response->assertOk();
        $this->assertStringContainsString('2026-weekly-meter-report.xlsx', (string) $response->headers->get('content-disposition'));

        $this->actingAs($user)
            ->getJson('/api/weekly-meter-report/download?year=2024')
            ->assertUnprocessable();
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
        $this->assertSame("ADDT'L FEES", $sheet->getCell('L2')->getValue());
        $this->assertSame(17533.0, (float) $sheet->getCell('N7')->getCalculatedValue());
    }

    public function test_additional_fees_use_allocated_amazon_and_walmart_column_f(): void
    {
        $user = $this->createAnalyst();
        $kit = $this->createStarterKitCategory();
        $c555 = $this->createCategory('c555_concrete_kits', 'C555 CONCRETE KITS', 'C555 Concrete Kits', 80);
        $batch = $this->createWeekSales($user, '2026-04-03', $kit, 2, 1246.00, 1);

        $amazonFile = $this->addFeesUpload($batch, 'amazon_fees');
        $this->addFeeRow($batch, $amazonFile, $kit, 'KIT-L6', 75.00, 10);
        $this->addFeeRow($batch, $amazonFile, $c555, 'C555-1', 25.00, 11);
        $this->addFeeRow($batch, $amazonFile, $kit, 'SHIP_UPS_GROUND', 100.00, 12);
        $this->addFeeAdjustment($batch, 'amazon', -40.00, $user);

        $walmartFile = $this->addFeesUpload($batch, 'walmart_fees');
        $this->addFeeRow($batch, $walmartFile, $kit, 'KIT-WM', 200.00, 20);
        $this->addFeeAdjustment($batch, 'walmart', -10.00, $user);

        $report = app(WeeklyMeterReportBuilder::class)->build(2026, 4, CarbonImmutable::parse('2026-09-21'));
        $kitRow = collect($report['rows'])->firstWhere('label', 'RHP L6 Kits');
        $c555Row = collect($report['rows'])->firstWhere('label', 'C555 Concrete Kits');

        $this->assertSame(40.0, $kitRow['additional_fees_total']);
        $this->assertSame(10.0, $c555Row['additional_fees_total']);
        $this->assertNotEquals(75.0, $kitRow['additional_fees_total']);
        $this->assertNotEquals(200.0, $kitRow['additional_fees_total']);
        $this->assertEqualsCanonicalizing(
            ['amazon', 'walmart'],
            array_column($kitRow['additional_fees'], 'source'),
        );
        $this->assertSame(1286.0, $kitRow['mtd_sales']);
        $this->assertSame(50.0, $report['totals']['additional_fees_total']);
        $this->assertSame(3.9, $report['totals']['additional_fees_percent']);
        $this->assertSame(1296.0, $report['totals']['mtd_sales']);
    }

    public function test_additional_fees_combine_amazon_misc_and_skip_weeks_without_uploaded_fees(): void
    {
        $user = $this->createAnalyst();
        $kit = $this->createStarterKitCategory();
        $misc = $this->createCategory('rhp_miscellaneous', 'RHP MISCELLANEOUS', 'BT Smart Loggers', 40);
        $miscL6 = $this->createCategory('rhp_miscellaneous_l6', 'RHP MISCELLANEOUS L6', 'RHP L6 Misc', 41);
        $onTotal = $this->createCategory('misc_on_total_sales_report', 'Misc (on Total Sales Report)', 'RHP L6 Misc', 42);

        $weekWithFees = $this->createWeekSales($user, '2026-04-03', $kit, 2, 1246.00, 1);
        $amazonFile = $this->addFeesUpload($weekWithFees, 'amazon_fees');
        $this->addFeeRow($weekWithFees, $amazonFile, $kit, 'KIT-L6', 50.00, 10);
        $this->addFeeRow($weekWithFees, $amazonFile, $misc, 'MISC-1', 30.00, 11);
        $this->addFeeRow($weekWithFees, $amazonFile, $miscL6, 'MISC-L6', 10.00, 12);
        $this->addFeeRow($weekWithFees, $amazonFile, $onTotal, 'MISC-TSR', 10.00, 13);
        $this->addFeeAdjustment($weekWithFees, 'amazon', -100.00, $user);

        $weekWithoutUpload = $this->createWeekSales($user, '2026-04-10', $kit, 18, 12549.00, 2);
        $this->addFeeAdjustment($weekWithoutUpload, 'amazon', -999.00, $user);

        $report = app(WeeklyMeterReportBuilder::class)->build(2026, 4, CarbonImmutable::parse('2026-09-21'));
        $kitRow = collect($report['rows'])->firstWhere('label', 'RHP L6 Kits');
        $miscRow = collect($report['rows'])->firstWhere('label', 'RHP L6 Misc');
        $loggerRow = collect($report['rows'])->firstWhere('label', 'BT Smart Loggers');

        $this->assertSame(50.0, $kitRow['additional_fees_total']);
        $this->assertSame(50.0, $miscRow['additional_fees_total']);
        $this->assertSame(0.0, $loggerRow['additional_fees_total']);
        $this->assertSame([['amount' => 50.0, 'sort_order' => 0, 'source' => 'amazon']], $miscRow['additional_fees']);
        $this->assertSame(100.0, $report['totals']['additional_fees_total']);
    }

    public function test_additional_fees_manual_put_route_is_removed(): void
    {
        $user = $this->createAnalyst();

        $this->actingAs($user)
            ->putJson('/api/weekly-meter-report/additional-fees', [
                'year' => 2026,
                'month' => 4,
                'model_label' => 'RHP L6 Kits',
                'fees' => [['amount' => 10]],
            ])
            ->assertMethodNotAllowed();
    }

    public function test_exporter_includes_additional_fees_column(): void
    {
        $user = $this->createAnalyst();
        $category = $this->createStarterKitCategory();
        $batch = $this->createWeekSales($user, '2026-04-17', $category, 26, 17533.00, 3);
        $amazonFile = $this->addFeesUpload($batch, 'amazon_fees');
        $this->addFeeRow($batch, $amazonFile, $category, 'KIT-L6', 80.00, 10);
        $this->addFeeAdjustment($batch, 'amazon', -200.00, $user);

        $generated = app(WeeklyMeterReportExporter::class)->export($batch, $user->id);
        $sheet = IOFactory::load(storage_path('app/private/'.$generated->storage_path))->getActiveSheet();

        $percentRow = 6 + count(WeeklyMeterModelCatalog::rows()) + 1;

        $this->assertSame("ADDT'L FEES", $sheet->getCell('L2')->getValue());
        $this->assertSame(200.0, (float) $sheet->getCell('L7')->getCalculatedValue());
        $this->assertSame(17733.0, (float) $sheet->getCell('N7')->getCalculatedValue());
        $this->assertEqualsWithDelta(0.011, (float) $sheet->getCell('L'.$percentRow)->getCalculatedValue(), 0.00001);
    }

    public function test_multi_category_meter_model_applies_quantity_multipliers_and_week_deltas(): void
    {
        $user = $this->createAnalyst();

        $pack5 = ProductCategory::query()->create([
            'code' => 'pack_5_rapid_rh_l6_smart_sensor',
            'name' => 'PACK, 5 ,RAPID RH L6, SMART SENSOR',
            'report_family' => 'rhp',
            'sales_analysis_bucket' => 'rhp',
            'weekly_meter_row_label' => 'RHP L6 5Pk Sensors*',
            'sort_order' => 32,
            'quantity_multiplier' => 1,
            'is_active' => true,
        ]);

        $valuePack25 = ProductCategory::query()->create([
            'code' => 'value_pack_25pc_rhp_l6_smart_sensor',
            'name' => 'VALUE PACK, 25PC, RHP L6 SMART SENSOR',
            'report_family' => 'rhp',
            'sales_analysis_bucket' => 'rhp',
            'weekly_meter_row_label' => 'RHP L6 5Pk Sensors*',
            'sort_order' => 34,
            'quantity_multiplier' => 5,
            'is_active' => true,
        ]);

        $weekOne = $this->createBatch($user, '2026-04-03');
        $this->addSalesRow($weekOne, $pack5, 10, 1000.00, 1);
        $this->addSalesRow($weekOne, $valuePack25, 10, 2000.00, 2);

        $weekTwo = $this->createBatch($user, '2026-04-10');
        $this->addSalesRow($weekTwo, $pack5, 20, 1500.00, 1);
        $this->addSalesRow($weekTwo, $valuePack25, 12, 2500.00, 2);

        $report = app(WeeklyMeterReportBuilder::class)->build(2026, 4, CarbonImmutable::parse('2026-09-21'));
        $row = collect($report['rows'])->firstWhere('label', 'RHP L6 5Pk Sensors*');

        // Week 1 raw: (10×1) + (10×5) = 60
        $this->assertSame(60.0, $row['weeks'][0]['units']);
        $this->assertSame(3000.0, $row['weeks'][0]['sales']);

        // Week 2 raw: (20×1) + (12×5) = 80; displayed: 80 − 60 = 20
        $this->assertSame(20.0, $row['weeks'][1]['units']);
        $this->assertSame(1000.0, $row['weeks'][1]['sales']);

        $this->assertSame(80.0, $row['mtd_units']);
        $this->assertSame(4000.0, $row['mtd_sales']);
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
        return $this->createCategory(
            'kit_rapid_rh_l6_starter_kit_plus_fahrenheit',
            'KIT, RAPID RH L6 STARTER KIT PLUS-FAHRENHEIT',
            'RHP L6 Kits',
            30,
        );
    }

    private function createCategory(string $code, string $name, string $meterLabel, int $sortOrder): ProductCategory
    {
        return ProductCategory::query()->create([
            'code' => $code,
            'name' => $name,
            'report_family' => 'rhp',
            'sales_analysis_bucket' => 'rhp',
            'weekly_meter_row_label' => $meterLabel,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);
    }

    private function addFeesUpload(ImportBatch $batch, string $fileType): UploadedFile
    {
        return UploadedFile::query()->create([
            'import_batch_id' => $batch->id,
            'file_type' => $fileType,
            'original_name' => $fileType.'.xlsx',
            'storage_path' => 'uploads/'.$batch->id.'/'.$fileType.'.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'size_bytes' => 100,
            'sha256_checksum' => hash('sha256', $batch->id.$fileType),
            'status' => 'imported',
        ]);
    }

    private function addFeeAdjustment(ImportBatch $batch, string $marketplace, float $amount, User $user): ImportBatchFeeAdjustment
    {
        return ImportBatchFeeAdjustment::query()->create([
            'import_batch_id' => $batch->id,
            'marketplace' => $marketplace,
            'amount' => $amount,
            'sort_order' => 0,
            'created_by_user_id' => $user->id,
        ]);
    }

    private function addFeeRow(
        ImportBatch $batch,
        UploadedFile $uploadedFile,
        ProductCategory $category,
        string $itemId,
        float $amount,
        int $sourceRow,
    ): SalesRow {
        return SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'uploaded_file_id' => $uploadedFile->id,
            'workbook_type' => $uploadedFile->file_type,
            'product_category_id' => $category->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => $sourceRow,
            'source_bucket' => $category->sales_analysis_bucket,
            'item_id' => $itemId,
            'description' => $category->name,
            'amount' => $amount,
            'classification_status' => 'matched',
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

    private function addSalesRow(
        ImportBatch $batch,
        ProductCategory $category,
        float $quantity,
        float $amount,
        int $sourceRow,
    ): SalesRow {
        return SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'product_category_id' => $category->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => $sourceRow,
            'source_bucket' => 'rhp',
            'item_id' => '880-R0002-012',
            'description' => $category->name,
            'quantity_ordered' => $quantity,
            'amount' => $amount,
            'classification_status' => 'matched',
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
        $batch = $this->createBatch($user, $weekEnding);
        $this->addSalesRow($batch, $category, $quantity, $amount, $sourceRow);

        return $batch;
    }
}
