<?php

namespace Tests\Feature;

use App\Domain\WeeklyAnalysis\Exports\Services\WeeklyReportExportService;
use App\Models\GeneratedReport;
use App\Models\ImportBatch;
use App\Models\MappingRule;
use App\Models\ProductCategory;
use App\Models\SalesRow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class WeeklyReportExportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_all_reclassifies_same_month_siblings_and_refreshes_weekly_meter_reports(): void
    {
        $user = $this->createAnalyst();
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

        $weekOne = $this->createBatch($user, '2026-04-03');
        $weekTwo = $this->createBatch($user, '2026-04-10');

        $weekOneRow = $this->createUnmatchedRow($weekOne, 1, 2, 1246.00);
        $weekTwoRow = $this->createUnmatchedRow($weekTwo, 2, 18, 12549.00);

        $reports = app(WeeklyReportExportService::class)->exportAll($weekTwo, $user->id);

        $weekOneRow->refresh();
        $weekTwoRow->refresh();

        $this->assertSame('matched', $weekOneRow->classification_status);
        $this->assertSame($category->id, $weekOneRow->product_category_id);
        $this->assertSame('rhp', $weekOneRow->source_bucket);

        $this->assertSame('matched', $weekTwoRow->classification_status);
        $this->assertSame($category->id, $weekTwoRow->product_category_id);
        $this->assertSame('rhp', $weekTwoRow->source_bucket);

        $this->assertCount(3, $reports);

        $weekOneMeter = GeneratedReport::query()
            ->where('import_batch_id', $weekOne->id)
            ->where('report_type', 'weekly_meter_report')
            ->first();
        $weekTwoMeter = GeneratedReport::query()
            ->where('import_batch_id', $weekTwo->id)
            ->where('report_type', 'weekly_meter_report')
            ->first();

        $this->assertNotNull($weekOneMeter);
        $this->assertNotNull($weekTwoMeter);
        $this->assertSame('completed', $weekOneMeter->status);
        $this->assertSame('completed', $weekTwoMeter->status);

        $weekOneSheet = IOFactory::load(storage_path('app/private/'.$weekOneMeter->storage_path))->getActiveSheet();
        $weekTwoSheet = IOFactory::load(storage_path('app/private/'.$weekTwoMeter->storage_path))->getActiveSheet();

        $this->assertSame(2.0, (float) $weekOneSheet->getCell('B7')->getCalculatedValue());
        $this->assertSame(1246.0, (float) $weekOneSheet->getCell('C7')->getCalculatedValue());
        $this->assertSame(16.0, (float) $weekTwoSheet->getCell('D7')->getCalculatedValue());
        $this->assertSame(11303.0, (float) $weekTwoSheet->getCell('E7')->getCalculatedValue());
    }

    public function test_regenerate_overwrites_existing_generated_reports(): void
    {
        $user = $this->createAnalyst();
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

        $batch = $this->createBatch($user, '2026-04-17');
        $this->createUnmatchedRow($batch, 3, 26, 17533.00);

        $first = app(WeeklyReportExportService::class)->exportAll($batch, $user->id);
        $salesAnalysisId = $first[0]->id;
        $meterId = $first[2]->id;
        $firstChecksum = $first[0]->sha256_checksum;

        SalesRow::query()->where('import_batch_id', $batch->id)->update([
            'amount' => 18000.00,
            'product_category_id' => null,
            'source_bucket' => 'raw',
            'classification_status' => 'unmatched',
            'mapping_rule_id' => null,
        ]);

        $second = app(WeeklyReportExportService::class)->exportAll($batch->refresh(), $user->id);

        $this->assertSame($salesAnalysisId, $second[0]->id);
        $this->assertSame($meterId, $second[2]->id);
        $this->assertNotSame($firstChecksum, $second[0]->sha256_checksum);
        $this->assertSame(1, GeneratedReport::query()
            ->where('import_batch_id', $batch->id)
            ->where('report_type', 'sales_analysis')
            ->count());
        $this->assertSame(1, GeneratedReport::query()
            ->where('import_batch_id', $batch->id)
            ->where('report_type', 'weekly_meter_report')
            ->count());
    }

    private function createAnalyst(): User
    {
        return User::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lyst',
            'email' => 'analyst-export-service@example.com',
            'password' => 'password',
            'role' => 'analyst',
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

    private function createUnmatchedRow(ImportBatch $batch, int $sourceRow, float $quantity, float $amount): SalesRow
    {
        return SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'product_category_id' => null,
            'source_sheet' => 'Sheet',
            'source_row_number' => $sourceRow,
            'source_bucket' => 'raw',
            'item_id' => '880-R0002-012',
            'description' => 'KIT, RAPID RH L6 STARTER KIT PLUS-FAHRENHEIT',
            'quantity_ordered' => $quantity,
            'amount' => $amount,
            'classification_status' => 'unmatched',
        ]);
    }
}
