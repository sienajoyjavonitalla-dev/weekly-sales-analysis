<?php

namespace Tests\Feature;

use App\Domain\WeeklyAnalysis\Exports\Services\SalesAnalysisWorkbookExporter;
use App\Models\ImportBatch;
use App\Models\ProductCategory;
use App\Models\SalesRow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class SalesAnalysisWorkbookExporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_source_sheet_tab_includes_all_uploaded_rows_with_amounts(): void
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

        SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => 2,
            'source_bucket' => 'rhp',
            'item_id' => '674-00004-001',
            'description' => 'THERMAL CAMERA, TC8850',
            'quantity_ordered' => 1,
            'amount' => 199,
            'classification_status' => 'matched',
        ]);

        SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => 5,
            'source_bucket' => 'raw',
            'item_id' => '674-00004-001',
            'description' => 'THERMAL CAMERA, TC8850 - NO CHARGE',
            'quantity_ordered' => 1,
            'amount' => 0,
            'classification_status' => 'matched',
        ]);

        $report = app(SalesAnalysisWorkbookExporter::class)->export($batch, $user->id);
        $path = storage_path('app/private/'.$report->storage_path);

        $sheet = IOFactory::load($path)->getSheetByName('Sheet');

        $this->assertSame('A9D1F7', strtoupper($sheet->getStyle('A1')->getFill()->getStartColor()->getRGB()));
        $this->assertSame('674-00004-001', $sheet->getCell('A2')->getCalculatedValue());
        $this->assertSame(199.0, (float) $sheet->getCell('K2')->getCalculatedValue());
        $this->assertSame('674-00004-001', $sheet->getCell('A3')->getCalculatedValue());
        $this->assertSame('-', $sheet->getCell('K3')->getCalculatedValue());
        $this->assertSame(4, $sheet->getHighestDataRow());
        $this->assertSame(2.0, (float) $sheet->getCell('J4')->getCalculatedValue());
        $this->assertSame(199.0, (float) $sheet->getCell('K4')->getCalculatedValue());
        $this->assertSame('A9D1F7', strtoupper($sheet->getStyle('A4')->getFill()->getStartColor()->getRGB()));
    }

    public function test_unassigned_rows_receive_subtotal_and_spacing_before_next_category(): void
    {
        $user = User::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lyst',
            'email' => 'analyst-unassigned@example.com',
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

        $miscCategory = ProductCategory::query()->create([
            'code' => 'rhp_miscellaneous',
            'name' => 'RHP MISCELLANEOUS',
            'report_family' => 'rhp',
            'sales_analysis_bucket' => 'rhp',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $nextCategory = ProductCategory::query()->create([
            'code' => 'floor_sentry',
            'name' => 'Floor Sentry',
            'report_family' => 'rhp',
            'sales_analysis_bucket' => 'rhp',
            'sort_order' => 18,
            'is_active' => true,
        ]);

        SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'product_category_id' => $miscCategory->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => 2,
            'source_bucket' => 'rhp',
            'item_id' => '880-R4100-005',
            'quantity_ordered' => 1,
            'amount' => 440,
            'classification_status' => 'matched',
        ]);

        SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'product_category_id' => null,
            'source_sheet' => 'Sheet',
            'source_row_number' => 3,
            'source_bucket' => 'rhp',
            'item_id' => '694-R0003-002',
            'quantity_ordered' => 5,
            'amount' => 495,
            'classification_status' => 'matched',
        ]);

        SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'product_category_id' => null,
            'source_sheet' => 'Sheet',
            'source_row_number' => 4,
            'source_bucket' => 'rhp',
            'item_id' => 'MISCELLANEOUS',
            'quantity_ordered' => 1,
            'amount' => 575,
            'classification_status' => 'matched',
        ]);

        SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'product_category_id' => $nextCategory->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => 5,
            'source_bucket' => 'rhp',
            'item_id' => '890-00080-001',
            'quantity_ordered' => 1,
            'amount' => 189,
            'classification_status' => 'matched',
        ]);

        $report = app(SalesAnalysisWorkbookExporter::class)->export($batch, $user->id);
        $sheet = IOFactory::load(storage_path('app/private/'.$report->storage_path))->getSheetByName('RHP');

        $this->assertSame('RHP MISCELLANEOUS', $sheet->getCell('A2')->getCalculatedValue());
        $this->assertTrue($sheet->getStyle('A2')->getFont()->getBold());
        $this->assertTrue($sheet->getStyle('A2')->getFont()->getItalic());
        $this->assertEquals(12, $sheet->getStyle('A2')->getFont()->getSize());
        $this->assertSame('694-R0003-002', $sheet->getCell('A6')->getCalculatedValue());
        $this->assertSame('MISCELLANEOUS', $sheet->getCell('A7')->getCalculatedValue());
        $this->assertSame('n/a', $sheet->getCell('J4')->getCalculatedValue());
        $this->assertTrue($sheet->getStyle('J4')->getFont()->getBold());
        $this->assertTrue($sheet->getStyle('K4')->getFont()->getBold());
        $this->assertSame('FFFF00', strtoupper($sheet->getStyle('A4')->getFill()->getStartColor()->getRGB()));
        $this->assertSame(440.0, (float) $sheet->getCell('K4')->getCalculatedValue());
        $this->assertSame('n/a', $sheet->getCell('J8')->getCalculatedValue());
        $this->assertSame(1070.0, (float) $sheet->getCell('K8')->getCalculatedValue());
        $this->assertSame('FFFF00', strtoupper($sheet->getStyle('A8')->getFill()->getStartColor()->getRGB()));
        $this->assertNull($sheet->getCell('A9')->getCalculatedValue());
        $this->assertSame('Floor Sentry', $sheet->getCell('A10')->getCalculatedValue());
        $this->assertTrue($sheet->getStyle('A10')->getFont()->getBold());
        $this->assertTrue($sheet->getStyle('A10')->getFont()->getItalic());
        $this->assertEquals(12, $sheet->getStyle('A10')->getFont()->getSize());
        $this->assertSame('890-00080-001', $sheet->getCell('A11')->getCalculatedValue());

        $grandTotalRow = $this->grandTotalRow($sheet);
        $this->assertSame(8.0, (float) $sheet->getCell('J'.$grandTotalRow)->getCalculatedValue());
        $this->assertSame(1699.0, (float) $sheet->getCell('K'.$grandTotalRow)->getCalculatedValue());
        $this->assertSame('A9D1F7', strtoupper($sheet->getStyle('A'.$grandTotalRow)->getFill()->getStartColor()->getRGB()));
    }

    public function test_category_quantity_multiplier_writes_adjusted_qty_and_feeds_grand_total(): void
    {
        $user = User::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lyst',
            'email' => 'analyst-multiplier@example.com',
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

        $packCategory = ProductCategory::query()->create([
            'code' => 'value_pack_50pc_rhp_l6_smart_sensor',
            'name' => 'VALUE PACK, 50PC, RHP L6 SMART SENSOR',
            'report_family' => 'rhp',
            'sales_analysis_bucket' => 'rhp',
            'sort_order' => 10,
            'quantity_multiplier' => 10,
            'is_active' => true,
        ]);

        $plainCategory = ProductCategory::query()->create([
            'code' => 'floor_sentry',
            'name' => 'Floor Sentry',
            'report_family' => 'rhp',
            'sales_analysis_bucket' => 'rhp',
            'sort_order' => 20,
            'quantity_multiplier' => 1,
            'is_active' => true,
        ]);

        SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'product_category_id' => $packCategory->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => 2,
            'source_bucket' => 'rhp',
            'item_id' => '880-R0050-011',
            'quantity_ordered' => 10,
            'amount' => 1000,
            'classification_status' => 'matched',
        ]);

        SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'product_category_id' => $packCategory->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => 3,
            'source_bucket' => 'rhp',
            'item_id' => '880-R0050-011IN',
            'quantity_ordered' => 4,
            'amount' => 400,
            'classification_status' => 'matched',
        ]);

        SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'product_category_id' => $plainCategory->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => 4,
            'source_bucket' => 'rhp',
            'item_id' => '890-00080-001',
            'quantity_ordered' => 2,
            'amount' => 200,
            'classification_status' => 'matched',
        ]);

        $report = app(SalesAnalysisWorkbookExporter::class)->export($batch, $user->id);
        $sheet = IOFactory::load(storage_path('app/private/'.$report->storage_path))->getSheetByName('RHP');

        // Pack category: header row 2, data 3-4, subtotal row 5
        $this->assertSame(14.0, (float) $sheet->getCell('J5')->getCalculatedValue());
        $this->assertSame(1400.0, (float) $sheet->getCell('K5')->getCalculatedValue());
        $this->assertSame(140.0, (float) $sheet->getCell('L5')->getCalculatedValue());

        // Plain category: header 6, data 7, subtotal 8
        $this->assertSame(2.0, (float) $sheet->getCell('J8')->getCalculatedValue());
        $this->assertSame(200.0, (float) $sheet->getCell('K8')->getCalculatedValue());
        $this->assertNull($sheet->getCell('L8')->getCalculatedValue());

        $grandTotalRow = $this->grandTotalRow($sheet);
        $this->assertSame(142.0, (float) $sheet->getCell('J'.$grandTotalRow)->getCalculatedValue());
        $this->assertSame(1600.0, (float) $sheet->getCell('K'.$grandTotalRow)->getCalculatedValue());
    }

    public function test_parts_tsd_sheet_writes_category_headers_for_legacy_sections(): void
    {
        $user = User::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lyst',
            'email' => 'analyst-parts-tsd@example.com',
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

        $miscCategory = ProductCategory::query()->create([
            'code' => 'misc_on_total_sales_report',
            'name' => 'Misc (on Total Sales Report)',
            'report_family' => 'parts_tsd',
            'sales_analysis_bucket' => 'parts_tsd',
            'sort_order' => 100,
            'is_active' => true,
        ]);

        SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'product_category_id' => $miscCategory->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => 2,
            'source_bucket' => 'parts_tsd',
            'item_id' => '674-00004-001',
            'description' => 'THERMAL, CAMERA, TC8650',
            'quantity_ordered' => 1,
            'amount' => 199,
            'classification_status' => 'matched',
        ]);

        $report = app(SalesAnalysisWorkbookExporter::class)->export($batch, $user->id);
        $sheet = IOFactory::load(storage_path('app/private/'.$report->storage_path))->getSheetByName('Parts & TSD');

        $this->assertSame('Misc (on Total Sales Report)', $sheet->getCell('A2')->getCalculatedValue());
        $this->assertSame('Misc (on Total Sales Report)', $sheet->getCell('B2')->getCalculatedValue());
        $this->assertSame('674-00004-001', $sheet->getCell('A3')->getCalculatedValue());
        $this->assertSame('THERMAL, CAMERA, TC8650', $sheet->getCell('B3')->getCalculatedValue());
    }

    public function test_state_sheet_is_omitted_by_default(): void
    {
        $user = User::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lyst',
            'email' => 'analyst-no-state@example.com',
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

        SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => 2,
            'source_bucket' => 'state',
            'item_id' => 'STATE-001',
            'quantity_ordered' => 1,
            'amount' => 50,
            'classification_status' => 'matched',
        ]);

        $report = app(SalesAnalysisWorkbookExporter::class)->export($batch, $user->id);
        $spreadsheet = IOFactory::load(storage_path('app/private/'.$report->storage_path));

        $this->assertSame(['RHP', 'Parts & TSD', 'Sheet'], $spreadsheet->getSheetNames());
        $this->assertSame(0, $report->summary['state_rows'] ?? null);
    }

    public function test_state_sheet_is_included_when_requested(): void
    {
        $user = User::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lyst',
            'email' => 'analyst-with-state@example.com',
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

        SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => 2,
            'source_bucket' => 'state',
            'item_id' => 'STATE-001',
            'quantity_ordered' => 1,
            'amount' => 50,
            'classification_status' => 'matched',
        ]);

        $report = app(SalesAnalysisWorkbookExporter::class)->export($batch, $user->id, true);
        $spreadsheet = IOFactory::load(storage_path('app/private/'.$report->storage_path));

        $this->assertSame(['RHP', 'Parts & TSD', 'State', 'Sheet'], $spreadsheet->getSheetNames());
        $this->assertSame(1, $report->summary['state_rows'] ?? null);
        $this->assertSame('STATE-001', $spreadsheet->getSheetByName('State')->getCell('A2')->getCalculatedValue());
    }

    public function test_rhp_sheet_writes_family_footer_matching_source_template(): void
    {
        $user = User::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lyst',
            'email' => 'analyst-footer@example.com',
            'password' => 'password',
            'role' => 'analyst',
        ]);

        $batch = ImportBatch::query()->create([
            'week_start' => '2026-04-01',
            'week_ending' => '2026-04-03',
            'status' => 'draft',
            'created_by_user_id' => $user->id,
            'source_system' => 'Traverse Global',
        ]);

        $rhp = ProductCategory::query()->create([
            'code' => 'pack_5_rapid_rh_l6_smart_sensor',
            'name' => 'PACK, 5 ,RAPID RH L6, SMART SENSOR',
            'report_family' => 'rhp',
            'sales_analysis_bucket' => 'rhp',
            'weekly_meter_row_label' => 'RHP L6 5Pk Sensors*',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        $cm = ProductCategory::query()->create([
            'code' => 'top_assy_orion_c555_concrete_kit_tested',
            'name' => 'TOP ASSY,ORION C555',
            'report_family' => 'rhp',
            'sales_analysis_bucket' => 'rhp',
            'weekly_meter_row_label' => 'C555 Concrete Kits',
            'sort_order' => 20,
            'is_active' => true,
        ]);
        $mre = ProductCategory::query()->create([
            'code' => 'meter_reach_extender',
            'name' => 'METER REACH EXTENDER',
            'report_family' => 'rhp',
            'sales_analysis_bucket' => 'rhp',
            'weekly_meter_row_label' => 'Handmeter Misc',
            'sort_order' => 30,
            'is_active' => true,
        ]);
        $parts = ProductCategory::query()->create([
            'code' => 'tsd_parts',
            'name' => 'TSD Parts',
            'report_family' => 'parts_tsd',
            'sales_analysis_bucket' => 'parts_tsd',
            'sort_order' => 40,
            'is_active' => true,
        ]);

        SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'product_category_id' => $rhp->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => 2,
            'source_bucket' => 'rhp',
            'item_id' => '880-R0000-009',
            'quantity_ordered' => 2,
            'amount' => 518,
            'classification_status' => 'matched',
        ]);
        SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'product_category_id' => $cm->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => 3,
            'source_bucket' => 'rhp',
            'item_id' => '890-00555-001',
            'quantity_ordered' => 1,
            'amount' => 381.5,
            'classification_status' => 'matched',
        ]);
        SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'product_category_id' => $mre->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => 4,
            'source_bucket' => 'rhp',
            'item_id' => '880-90001-001',
            'quantity_ordered' => 1,
            'amount' => 45,
            'classification_status' => 'matched',
        ]);
        SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'product_category_id' => $parts->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => 5,
            'source_bucket' => 'parts_tsd',
            'item_id' => '648-00902-030',
            'quantity_ordered' => 1,
            'amount' => 744,
            'classification_status' => 'matched',
        ]);

        $report = app(SalesAnalysisWorkbookExporter::class)->export($batch, $user->id);
        $sheet = IOFactory::load(storage_path('app/private/'.$report->storage_path))->getSheetByName('RHP');

        $grandTotalRow = $this->grandTotalRow($sheet);
        $this->assertSame('RHP', $sheet->getCell('B'.($grandTotalRow + 5))->getValue());
        $this->assertSame('CM', $sheet->getCell('B'.($grandTotalRow + 6))->getValue());
        $this->assertSame('Sm. Wood', $sheet->getCell('B'.($grandTotalRow + 7))->getValue());
        $this->assertSame('M.R.E', $sheet->getCell('B'.($grandTotalRow + 8))->getValue());
        $this->assertSame('Lg. Wood', $sheet->getCell('B'.($grandTotalRow + 9))->getValue());
        $this->assertSame(2.0, (float) $sheet->getCell('C'.($grandTotalRow + 5))->getCalculatedValue());
        $this->assertSame(518.0, (float) $sheet->getCell('D'.($grandTotalRow + 5))->getCalculatedValue());
        $this->assertSame(1.0, (float) $sheet->getCell('C'.($grandTotalRow + 6))->getCalculatedValue());
        $this->assertSame(1.0, (float) $sheet->getCell('C'.($grandTotalRow + 8))->getCalculatedValue());
        $this->assertSame(4.0, (float) $sheet->getCell('C'.($grandTotalRow + 10))->getCalculatedValue());
        $this->assertSame(944.5, (float) $sheet->getCell('D'.($grandTotalRow + 10))->getCalculatedValue());
        $this->assertSame(744.0, (float) $sheet->getCell('D'.($grandTotalRow + 11))->getCalculatedValue());
        $this->assertSame(1688.5, (float) $sheet->getCell('D'.($grandTotalRow + 18))->getCalculatedValue());
        $this->assertSame('Income Statement:', $sheet->getCell('C'.($grandTotalRow + 19))->getValue());
        $this->assertSame(-1688.5, (float) $sheet->getCell('D'.($grandTotalRow + 20))->getCalculatedValue());
    }

    private function grandTotalRow(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): int
    {
        for ($row = 2, $last = $sheet->getHighestDataRow(); $row <= $last; $row++) {
            $fill = strtoupper($sheet->getStyle('A'.$row)->getFill()->getStartColor()->getRGB());
            if ($fill === 'A9D1F7' && is_numeric($sheet->getCell('J'.$row)->getValue())) {
                return $row;
            }
        }

        $this->fail('Grand total row was not found.');
    }
}
