<?php

namespace Tests\Feature;

use App\Domain\WeeklyAnalysis\Exports\Services\MarketplaceFeesWorkbookExporter;
use App\Domain\WeeklyAnalysis\Imports\Enums\WorkbookType;
use App\Domain\WeeklyAnalysis\Imports\Parsers\AmazonFeesWorkbookParser;
use App\Models\ImportBatch;
use App\Models\ImportBatchFeeAdjustment;
use App\Models\MappingRule;
use App\Models\ProductCategory;
use App\Models\SalesRow;
use App\Models\UploadedFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile as HttpUploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class MarketplaceFeesWorkbookTest extends TestCase
{
    use RefreshDatabase;

    public function test_parser_reads_raw_amazon_fees_columns(): void
    {
        $path = $this->writeFeesWorkbook([
            ['AMA', '880-R0000-009', 'PACK, 5 ,RAPID RH L6, SMART SENSOR', 259, null, null, '5/1/2026', '146609', 'PO-1', 'AL', 'AMA027', 'note'],
            ['AMA', '890-00555-001', 'TOP ASSY,ORION C555', 545, null, null, '5/4/2026', '146600', 'PO-2', 'KY', 'ALH004', null],
        ]);

        $parsed = (new AmazonFeesWorkbookParser())->parse($path);

        $this->assertTrue($parsed->isValid());
        $this->assertCount(2, $parsed->rows);
        $this->assertSame('880-R0000-009', $parsed->rows[0]['item_id']);
        $this->assertSame(259, $parsed->rows[0]['amount']);
        $this->assertSame('PO-1', $parsed->rows[0]['customer_po_number']);
        $this->assertSame('note', $parsed->rows[0]['notes']);
        $this->assertSame(WorkbookType::AmazonFees, $parsed->type);
    }

    public function test_parser_reads_compact_fees_headers_without_empty_ef_columns(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sheet');
        $sheet->fromArray([
            'Sales Rep 1 ID',
            'Item ID',
            'Description',
            'Amount',
            'Invoice Date',
            'Invoice Number',
            'Customer PO Number',
            'Ship to State',
            'Customer ID',
            'Notes',
        ], null, 'A1');
        $sheet->fromArray(
            ['AMA', '880-R0000-009', 'PACK, 5 ,RAPID RH L6, SMART SENSOR', 259, '5/1/2026', '146609', 'PO-1', 'AL', 'AMA027', 'note'],
            null,
            'A2',
        );

        $path = tempnam(sys_get_temp_dir(), 'fees-compact').'.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

        $parsed = (new AmazonFeesWorkbookParser())->parse($path);

        $this->assertTrue($parsed->isValid());
        $this->assertCount(1, $parsed->rows);
        $this->assertSame('880-R0000-009', $parsed->rows[0]['item_id']);
        $this->assertSame('PO-1', $parsed->rows[0]['customer_po_number']);
        $this->assertSame('note', $parsed->rows[0]['notes']);
        $this->assertSame('AMA027', $parsed->rows[0]['customer_id']);
    }

    public function test_exporter_writes_category_headers_totals_formulas_and_family_summary(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createBatch($user);
        $uploadedFile = $this->createFeesUpload($batch, 'amazon_fees');
        $rhp = $this->createCategory('pack_5_rapid_rh_l6_smart_sensor', 'PACK, 5 ,RAPID RH L6, SMART SENSOR', 'RHP L6 5Pk Sensors*', 10);
        $cm = $this->createCategory('top_assy_orion_c555_concrete_kit_tested', 'TOP ASSY,ORION C555', 'C555 Concrete Kits', 20);

        $this->createFeeRow($batch, $uploadedFile, $rhp, '880-R0000-009', 259, 2);
        $this->createFeeRow($batch, $uploadedFile, $rhp, '880-R0000-009', 518, 3);
        $this->createFeeRow($batch, $uploadedFile, $cm, '890-00555-001', 545, 4);

        ImportBatchFeeAdjustment::query()->create([
            'import_batch_id' => $batch->id,
            'marketplace' => 'amazon',
            'amount' => 100.00,
            'sort_order' => 0,
        ]);
        ImportBatchFeeAdjustment::query()->create([
            'import_batch_id' => $batch->id,
            'marketplace' => 'amazon',
            'amount' => 50.50,
            'sort_order' => 1,
        ]);

        $report = app(MarketplaceFeesWorkbookExporter::class)->export(
            $batch,
            WorkbookType::AmazonFees,
            $user->id,
        );

        $this->assertSame('amazon_fees', $report->report_type);
        $this->assertSame('completed', $report->status);

        $sheet = IOFactory::load(storage_path('app/private/'.$report->storage_path))->getActiveSheet();

        $this->assertSame('Sales Rep 1 ID', $sheet->getCell('A1')->getValue());
        $this->assertSame('A9D1F7', $sheet->getStyle('A1')->getFill()->getStartColor()->getRGB());
        $this->assertSame('FFE699', $sheet->getStyle('A2')->getFill()->getStartColor()->getRGB());
        $this->assertSame('PACK, 5 ,RAPID RH L6, SMART SENSOR', $sheet->getCell('A2')->getValue());
        $this->assertSame('880-R0000-009', $sheet->getCell('B3')->getValue());
        $this->assertSame('=SUM(D3:D4)', $sheet->getCell('D5')->getValue());
        $this->assertSame('=D5/$D$16', $sheet->getCell('E5')->getValue());
        $this->assertSame('=$F$17*E5', $sheet->getCell('F5')->getValue());
        $this->assertNull($sheet->getCell('E3')->getValue());
        $this->assertSame('TOP ASSY,ORION C555', $sheet->getCell('A7')->getValue());
        $this->assertSame('=SUM(D2:D15)/2', $sheet->getCell('D16')->getValue());
        $this->assertSame('=100+50.5', $sheet->getCell('F17')->getValue());
        $this->assertSame('RHP', $sheet->getCell('C22')->getValue());
        $this->assertSame('=F5', $sheet->getCell('D22')->getValue());
        $this->assertSame('CM', $sheet->getCell('C23')->getValue());
        $this->assertSame('=F9', $sheet->getCell('D23')->getValue());
        $this->assertSame('Lg Wood', $sheet->getCell('C26')->getValue());
        $this->assertSame(0, $sheet->getCell('D26')->getValue());
    }

    public function test_exporter_combines_misc_categories_into_rhp_miscellaneous(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createBatch($user);
        $uploadedFile = $this->createFeesUpload($batch, 'amazon_fees');
        $rhpMisc = $this->createCategory('rhp_miscellaneous', 'RHP MISCELLANEOUS', 'RHP L6 Misc', 10);
        $rhpMiscL6 = $this->createCategory('rhp_miscellaneous_l6', 'RHP MISCELLANEOUS L6', 'RHP L6 Misc', 50);
        $tsrMisc = $this->createCategory('misc_on_total_sales_report', 'Misc (on Total Sales Report)', '', 100);

        $this->createFeeRow($batch, $uploadedFile, $rhpMisc, '880-R4100-005', 40, 2);
        $this->createFeeRow($batch, $uploadedFile, $rhpMiscL6, '880-L6MISC-001', 18, 3);
        $this->createFeeRow($batch, $uploadedFile, $tsrMisc, '674-00004-001', 25, 4);

        $report = app(MarketplaceFeesWorkbookExporter::class)->export(
            $batch,
            WorkbookType::AmazonFees,
            $user->id,
        );

        $sheet = IOFactory::load(storage_path('app/private/'.$report->storage_path))->getActiveSheet();

        $this->assertSame('RHP MISCELLANEOUS', $sheet->getCell('A2')->getValue());
        $this->assertSame('880-R4100-005', $sheet->getCell('B3')->getValue());
        $this->assertSame('880-L6MISC-001', $sheet->getCell('B4')->getValue());
        $this->assertSame('674-00004-001', $sheet->getCell('B5')->getValue());
        $this->assertSame('=SUM(D3:D5)', $sheet->getCell('D6')->getValue());

        $categoryHeaders = [];
        foreach ($sheet->getRowIterator() as $row) {
            $value = $sheet->getCell('A'.$row->getRowIndex())->getValue();
            if (is_string($value) && $value !== '') {
                $categoryHeaders[] = $value;
            }
        }

        $this->assertSame(1, collect($categoryHeaders)->filter(
            fn (string $header): bool => $header === 'RHP MISCELLANEOUS'
        )->count());
        $this->assertNotContains('Misc (on Total Sales Report)', $categoryHeaders);
        $this->assertNotContains('RHP MISCELLANEOUS L6', $categoryHeaders);
    }

    public function test_amazon_fees_exporter_omits_shipping_rows(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createBatch($user);
        $uploadedFile = $this->createFeesUpload($batch, 'amazon_fees');
        $category = $this->createCategory('pack_5_rapid_rh_l6_smart_sensor', 'PACK 5', 'RHP L6 5Pk Sensors*', 10);

        $this->createFeeRow($batch, $uploadedFile, $category, '880-R0000-009', 259, 2);
        $this->createFeeRow($batch, $uploadedFile, null, 'SHIP UPS_GROUND', 6.99, 3, 'SHIPPING - USING UPS GROUND SERVICE');
        $this->createFeeRow($batch, $uploadedFile, null, 'SHIP_UPS_EXPEDITED', 0.87, 4, 'SHIPPING - USING UPS WORLDWIDE EXPEDITED');

        $report = app(MarketplaceFeesWorkbookExporter::class)->export(
            $batch,
            WorkbookType::AmazonFees,
            $user->id,
        );

        $sheet = IOFactory::load(storage_path('app/private/'.$report->storage_path))->getActiveSheet();
        $itemIds = [];
        foreach ($sheet->getRowIterator() as $row) {
            $itemIds[] = (string) $sheet->getCell('B'.$row->getRowIndex())->getValue();
        }

        $this->assertContains('880-R0000-009', $itemIds);
        $this->assertNotContains('SHIP UPS_GROUND', $itemIds);
        $this->assertNotContains('SHIP_UPS_EXPEDITED', $itemIds);
        $this->assertSame(1, $report->summary['row_count']);
    }

    public function test_walmart_fees_exporter_keeps_misc_on_total_sales_report_separate(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createBatch($user);
        $uploadedFile = $this->createFeesUpload($batch, 'walmart_fees');
        $rhpMisc = $this->createCategory('rhp_miscellaneous', 'RHP MISCELLANEOUS', 'RHP L6 Misc', 10);
        $tsrMisc = $this->createCategory('misc_on_total_sales_report', 'Misc (on Total Sales Report)', '', 100);

        $this->createFeeRow($batch, $uploadedFile, $rhpMisc, '880-R4100-005', 40, 2);
        $this->createFeeRow($batch, $uploadedFile, $tsrMisc, '674-00004-001', 25, 3);

        $report = app(MarketplaceFeesWorkbookExporter::class)->export(
            $batch,
            WorkbookType::WalmartFees,
            $user->id,
        );

        $sheet = IOFactory::load(storage_path('app/private/'.$report->storage_path))->getActiveSheet();

        $this->assertSame('RHP MISCELLANEOUS', $sheet->getCell('A2')->getValue());
        $this->assertSame('880-R4100-005', $sheet->getCell('B3')->getValue());
        $this->assertSame('Misc (on Total Sales Report)', $sheet->getCell('A6')->getValue());
        $this->assertSame('674-00004-001', $sheet->getCell('B7')->getValue());
    }

    public function test_upload_classifies_and_marks_pending_reconcile_for_unmapped_fees(): void
    {
        $user = $this->createAnalyst();
        $path = $this->writeFeesWorkbook([
            ['AMA', 'UNMAPPED-ITEM', 'Unknown part', 100, null, null, '5/1/2026', '1', 'PO', 'FL', 'AMA027', null],
        ]);

        $response = $this->actingAs($user)->post('/api/workbook-imports', [
            'week_start' => '2026-05-01',
            'week_ending' => '2026-05-07',
            'amazon_fees' => new HttpUploadedFile(
                $path,
                'RAW AMA Fees.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true,
            ),
        ]);

        $response->assertCreated();
        $batchId = $response->json('data.import_batch.id');
        $this->assertSame(1, $response->json('data.summary.classification.reconcilable_unmatched_by_type.amazon_fees'));
        $this->assertDatabaseHas('uploaded_files', [
            'import_batch_id' => $batchId,
            'file_type' => 'amazon_fees',
            'status' => 'pending_reconcile',
        ]);
        $this->assertDatabaseHas('sales_rows', [
            'import_batch_id' => $batchId,
            'workbook_type' => 'amazon_fees',
            'item_id' => 'UNMAPPED-ITEM',
            'classification_status' => 'unmatched',
        ]);
    }

    public function test_unmatched_fees_can_be_resolved_then_generated(): void
    {
        $user = $this->createAnalyst();
        $category = $this->createCategory('pack_5_rapid_rh_l6_smart_sensor', 'PACK 5', 'RHP L6 5Pk Sensors*', 10);
        $path = $this->writeFeesWorkbook([
            ['AMA', 'UNMAPPED-ITEM', 'Unknown part', 100, null, null, '5/1/2026', '1', 'PO', 'FL', 'AMA027', null],
        ]);

        $response = $this->actingAs($user)->post('/api/workbook-imports', [
            'week_start' => '2026-05-08',
            'week_ending' => '2026-05-14',
            'amazon_fees' => new HttpUploadedFile(
                $path,
                'RAW AMA Fees.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true,
            ),
        ]);

        $response->assertCreated();
        $batchId = $response->json('data.import_batch.id');

        $this->actingAs($user)->postJson("/api/import-batches/{$batchId}/resolve-unmatched-items", [
            'file_type' => 'amazon_fees',
            'resolutions' => [
                ['item_id' => 'UNMAPPED-ITEM', 'product_category_id' => $category->id],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('uploaded_files', [
            'import_batch_id' => $batchId,
            'file_type' => 'amazon_fees',
            'status' => 'imported',
        ]);
        $this->assertDatabaseHas('sales_rows', [
            'import_batch_id' => $batchId,
            'workbook_type' => 'amazon_fees',
            'item_id' => 'UNMAPPED-ITEM',
            'classification_status' => 'manual',
            'product_category_id' => $category->id,
        ]);

        $this->actingAs($user)
            ->putJson("/api/import-batches/{$batchId}/fee-adjustments", [
                'marketplace' => 'amazon',
                'fees' => [],
            ])
            ->assertOk()
            ->assertJsonPath('data.total', 0)
            ->assertJsonPath('data.report.report_type', 'amazon_fees')
            ->assertJsonPath('data.report.status', 'completed');

        $this->assertDatabaseHas('generated_reports', [
            'import_batch_id' => $batchId,
            'report_type' => 'amazon_fees',
            'status' => 'completed',
        ]);
    }

    public function test_fee_adjustments_can_be_saved_and_are_returned_on_dashboard(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createBatch($user);
        $this->createFeesUpload($batch, 'amazon_fees');

        $this->actingAs($user)->putJson("/api/import-batches/{$batch->id}/fee-adjustments", [
            'marketplace' => 'amazon',
            'fees' => [
                ['amount' => 8878.33],
                ['amount' => 748.62],
            ],
        ])->assertOk()->assertJsonPath('data.total', 9626.95);

        $dashboard = $this->actingAs($user)
            ->getJson('/api/dashboard?year=2026&month=5')
            ->assertOk()
            ->json('data');

        $week = collect($dashboard['weeks'])->firstWhere('import_batch_id', $batch->id);
        $this->assertEqualsWithDelta(9626.95, $week['amazon_fees_total'], 0.001);
        $this->assertEqualsWithDelta(0.0, $week['walmart_fees_total'], 0.001);
    }

    public function test_calendar_includes_generated_amazon_fees_report(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createBatch($user);
        $uploadedFile = $this->createFeesUpload($batch, 'amazon_fees');
        $category = $this->createCategory('pack_5_rapid_rh_l6_smart_sensor', 'PACK 5', 'RHP L6 5Pk Sensors*', 10);
        $this->createFeeRow($batch, $uploadedFile, $category, '880-R0000-009', 259, 2);

        MappingRule::query()->create([
            'name' => 'sensor pack',
            'product_category_id' => $category->id,
            'source_type' => 'sales_analysis',
            'match_field' => 'item_id',
            'match_operator' => 'starts_with',
            'pattern' => '880-R0000-009',
            'target_bucket' => 'rhp',
            'priority' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->postJson("/api/import-batches/{$batch->id}/generated-reports")
            ->assertCreated();

        $response = $this->actingAs($user)
            ->getJson('/api/report-calendar?year=2026&month=5')
            ->assertOk();

        $reports = collect($response->json('data.weeks.0.reports'))->pluck('report_type')->all();
        $this->assertContains('amazon_fees', $reports);
        $this->assertContains('sales_analysis', $reports);
        $this->assertContains('weekly_meter_report', $reports);
    }

    /**
     * @param  list<array{0:string,1:string,2:string,3:float|int,4:?mixed,5:?mixed,6:string,7:string,8:?string,9:?string,10:?string,11:?string}>  $rows
     */
    private function writeFeesWorkbook(array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sheet');
        $sheet->fromArray([
            'Sales Rep 1 ID',
            'Item ID',
            'Description',
            'Amount',
            null,
            null,
            'Invoice Date',
            'Invoice Number',
            'Customer PO Number',
            'Ship to State',
            'Customer ID',
            'Notes',
        ], null, 'A1');

        $rowNumber = 2;
        foreach ($rows as $row) {
            $sheet->fromArray($row, null, 'A'.$rowNumber);
            $rowNumber++;
        }

        $path = tempnam(sys_get_temp_dir(), 'fees').'.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

        return $path;
    }

    private function createAnalyst(): User
    {
        return User::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lyst',
            'email' => 'analyst-fees@example.com',
            'password' => 'password',
            'role' => 'analyst',
        ]);
    }

    private function createBatch(User $user): ImportBatch
    {
        return ImportBatch::query()->create([
            'week_start' => '2026-05-01',
            'week_ending' => '2026-05-07',
            'status' => 'draft',
            'created_by_user_id' => $user->id,
            'source_system' => 'Traverse Global fees',
        ]);
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

    private function createFeesUpload(ImportBatch $batch, string $fileType): UploadedFile
    {
        return UploadedFile::query()->create([
            'import_batch_id' => $batch->id,
            'file_type' => $fileType,
            'original_name' => $fileType.'.xlsx',
            'storage_path' => 'uploads/'.$batch->id.'/'.$fileType.'.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'size_bytes' => 100,
            'sha256_checksum' => 'abc',
            'status' => 'imported',
        ]);
    }

    private function createFeeRow(
        ImportBatch $batch,
        UploadedFile $uploadedFile,
        ?ProductCategory $category,
        string $itemId,
        float $amount,
        int $sourceRow,
        ?string $description = null,
    ): SalesRow {
        return SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'uploaded_file_id' => $uploadedFile->id,
            'workbook_type' => $uploadedFile->file_type,
            'product_category_id' => $category?->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => $sourceRow,
            'source_bucket' => $category?->sales_analysis_bucket ?? 'raw',
            'item_id' => $itemId,
            'description' => $description ?? $category?->name,
            'sales_rep_id' => 'AMA',
            'amount' => $amount,
            'classification_status' => $category === null ? 'unmatched' : 'matched',
            'raw_values' => [
                'customer_po_number' => 'PO-'.$sourceRow,
                'notes' => null,
            ],
        ]);
    }
}
