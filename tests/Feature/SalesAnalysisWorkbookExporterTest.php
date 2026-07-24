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
        $this->assertSame(440.0, (float) $sheet->getCell('K4')->getCalculatedValue());
        $this->assertSame('n/a', $sheet->getCell('J8')->getCalculatedValue());
        $this->assertSame(1070.0, (float) $sheet->getCell('K8')->getCalculatedValue());
        $this->assertNull($sheet->getCell('A9')->getCalculatedValue());
        $this->assertSame('Floor Sentry', $sheet->getCell('A10')->getCalculatedValue());
        $this->assertTrue($sheet->getStyle('A10')->getFont()->getBold());
        $this->assertTrue($sheet->getStyle('A10')->getFont()->getItalic());
        $this->assertEquals(12, $sheet->getStyle('A10')->getFont()->getSize());
        $this->assertSame('890-00080-001', $sheet->getCell('A11')->getCalculatedValue());

        $grandTotalRow = $sheet->getHighestDataRow();
        $this->assertSame(8.0, (float) $sheet->getCell('J'.$grandTotalRow)->getCalculatedValue());
        $this->assertSame(1699.0, (float) $sheet->getCell('K'.$grandTotalRow)->getCalculatedValue());
        $this->assertSame('A9D1F7', strtoupper($sheet->getStyle('A'.$grandTotalRow)->getFill()->getStartColor()->getRGB()));
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
}
