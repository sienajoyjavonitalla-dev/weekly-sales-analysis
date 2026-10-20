<?php

namespace Tests\Feature;

use App\Domain\WeeklyAnalysis\Exports\Services\SalesAnalysisWorkbookExporter;
use App\Models\GeneratedReport;
use App\Models\ImportBatch;
use App\Models\ProductCategory;
use App\Models\SalesRow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneratedReportPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_returns_sheets_matching_the_generated_workbook(): void
    {
        [$user, $batch] = $this->createBatch();

        $misc = ProductCategory::query()->create([
            'code' => 'rhp_miscellaneous',
            'name' => 'RHP MISCELLANEOUS',
            'report_family' => 'rhp',
            'sales_analysis_bucket' => 'rhp',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'product_category_id' => $misc->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => 2,
            'source_bucket' => 'rhp',
            'item_id' => '880-R4100-005',
            'quantity_ordered' => 1,
            'amount' => 1440.5,
            'classification_status' => 'matched',
        ]);

        $report = app(SalesAnalysisWorkbookExporter::class)->export($batch, $user->id);

        $response = $this->actingAs($user)
            ->getJson("/api/generated-reports/{$report->id}/preview")
            ->assertOk()
            ->assertJsonPath('data.report.id', $report->id)
            ->assertJsonPath('data.report.import_batch.week_ending', '2026-02-07');

        $sheets = collect($response->json('data.sheets'));

        $this->assertSame(['RHP', 'Parts & TSD', 'Sheet'], $sheets->pluck('title')->all());

        $rhp = $sheets->firstWhere('title', 'RHP');
        $styles = $rhp['styles'];
        $rows = collect($rhp['rows'])->keyBy('n');

        // Header row: blue fill.
        $this->assertSame('Item ID', $rows[1]['cells'][0]['v']);
        $this->assertSame('A9D1F7', $styles[$rows[1]['cells'][0]['s']]['fill']);

        // Category header: merged B:K, bold italic 12pt.
        $categoryRow = $rows[2]['cells'];
        $this->assertSame('RHP MISCELLANEOUS', $categoryRow[0]['v']);
        $this->assertSame(10, $categoryRow[1]['colspan']);
        $this->assertCount(3, $categoryRow);
        $this->assertTrue($styles[$categoryRow[0]['s']]['bold']);
        $this->assertTrue($styles[$categoryRow[0]['s']]['italic']);
        $this->assertEquals(12, $styles[$categoryRow[0]['s']]['size']);

        // Data row.
        $this->assertSame('880-R4100-005', $rows[3]['cells'][0]['v']);
        $this->assertSame('1,440.50', trim($rows[3]['cells'][10]['v']));
        $this->assertSame('right', $styles[$rows[3]['cells'][10]['s']]['align']);

        // Subtotal row: yellow fill on every column, n/a quantity for miscellaneous.
        $subtotal = $rows[4]['cells'];
        $this->assertCount(12, $subtotal);
        $this->assertSame('FFFF00', $styles[$subtotal[0]['s']]['fill']);
        $this->assertSame('n/a', $subtotal[9]['v']);

        // Grand total row: blue fill.
        $grandTotal = $rows[5]['cells'];
        $this->assertSame('A9D1F7', $styles[$grandTotal[0]['s']]['fill']);
        $this->assertSame('1,440.50', trim($grandTotal[10]['v']));
    }

    public function test_preview_rejects_other_report_types(): void
    {
        [$user, $batch] = $this->createBatch();

        $report = GeneratedReport::query()->create([
            'import_batch_id' => $batch->id,
            'report_type' => 'total_sales_report',
            'status' => 'completed',
            'file_name' => 'x.xlsx',
            'storage_path' => 'generated-reports/x.xlsx',
        ]);

        $this->actingAs($user)
            ->getJson("/api/generated-reports/{$report->id}/preview")
            ->assertStatus(422);
    }

    public function test_preview_requires_authentication(): void
    {
        [, $batch] = $this->createBatch();

        $report = GeneratedReport::query()->create([
            'import_batch_id' => $batch->id,
            'report_type' => 'sales_analysis',
            'status' => 'completed',
            'file_name' => 'x.xlsx',
            'storage_path' => 'generated-reports/x.xlsx',
        ]);

        $this->getJson("/api/generated-reports/{$report->id}/preview")
            ->assertUnauthorized();
    }

    /**
     * @return array{0: User, 1: ImportBatch}
     */
    private function createBatch(): array
    {
        $user = User::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lyst',
            'email' => 'analyst-preview@example.com',
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

        return [$user, $batch];
    }
}
