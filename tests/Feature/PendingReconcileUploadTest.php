<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\MappingRule;
use App\Models\ProductCategory;
use App\Models\SalesRow;
use App\Models\UploadedFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendingReconcileUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_discard_pending_reconciles_removes_unresolved_sales_analysis_upload(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createBatch($user);
        $uploadedFile = $this->createSalesAnalysisUpload($batch, 'pending_reconcile');

        $this->createUnmatchedRow($batch, $uploadedFile, 'ITEM-A');

        $response = $this->actingAs($user)->postJson('/api/import-batches/discard-pending-reconciles');

        $response
            ->assertOk()
            ->assertJsonPath('data.discarded_count', 1);

        $this->assertDatabaseMissing('uploaded_files', ['id' => $uploadedFile->id]);
        $this->assertDatabaseMissing('sales_rows', ['import_batch_id' => $batch->id]);
        $this->assertDatabaseMissing('import_batches', ['id' => $batch->id]);
    }

    public function test_bulk_resolve_confirms_pending_reconcile_upload(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createBatch($user);
        $category = $this->createCategory();
        $uploadedFile = $this->createSalesAnalysisUpload($batch, 'pending_reconcile');

        $this->createUnmatchedRow($batch, $uploadedFile, 'ITEM-A');

        $this->actingAs($user)->postJson("/api/import-batches/{$batch->id}/resolve-unmatched-items", [
            'resolutions' => [
                ['item_id' => 'ITEM-A', 'product_category_id' => $category->id],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('uploaded_files', [
            'id' => $uploadedFile->id,
            'status' => 'imported',
        ]);
    }

    public function test_pending_reconcile_upload_cannot_be_downloaded(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createBatch($user);
        $uploadedFile = UploadedFile::query()->create([
            'import_batch_id' => $batch->id,
            'file_type' => 'sales_analysis',
            'original_name' => 'Sales Analysis.xlsx',
            'storage_path' => 'uploads/'.$batch->id.'/sales_analysis-test.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'size_bytes' => 100,
            'sha256_checksum' => 'abc123',
            'status' => 'pending_reconcile',
        ]);

        $this->actingAs($user)
            ->getJson('/api/uploaded-files/'.$uploadedFile->id.'/download')
            ->assertStatus(422);
    }

    public function test_classify_confirms_pending_reconcile_when_mapping_rule_clears_unmatched(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createBatch($user);
        $category = $this->createCategory();
        $uploadedFile = $this->createSalesAnalysisUpload($batch, 'pending_reconcile');
        $row = $this->createUnmatchedRow($batch, $uploadedFile, '880-TEST-001');

        MappingRule::query()->create([
            'name' => 'test rule → 880-TEST-001',
            'product_category_id' => $category->id,
            'source_type' => 'sales_analysis',
            'match_field' => 'item_id',
            'match_operator' => 'exact',
            'pattern' => '880-TEST-001',
            'target_bucket' => 'rhp',
            'priority' => 100,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->postJson("/api/import-batches/{$batch->id}/classify-sales-rows")
            ->assertOk()
            ->assertJsonPath('data.reconcilable_unmatched', 0)
            ->assertJsonPath('data.matched', 1);

        $row->refresh();

        $this->assertSame('matched', $row->classification_status);
        $this->assertSame('rhp', $row->source_bucket);
        $this->assertSame($category->id, $row->product_category_id);
        $this->assertDatabaseHas('uploaded_files', [
            'id' => $uploadedFile->id,
            'status' => 'imported',
        ]);
        $this->actingAs($user)
            ->getJson("/api/import-batches/{$batch->id}/unmatched-item-groups")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    private function createAnalyst(): User
    {
        return User::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lyst',
            'email' => 'analyst@example.com',
            'password' => 'password',
            'role' => 'analyst',
        ]);
    }

    private function createBatch(User $user): ImportBatch
    {
        return ImportBatch::query()->create([
            'week_start' => '2026-04-11',
            'week_ending' => '2026-04-17',
            'status' => 'draft',
            'created_by_user_id' => $user->id,
            'source_system' => 'Traverse Global',
        ]);
    }

    private function createCategory(): ProductCategory
    {
        return ProductCategory::query()->create([
            'code' => 'test_category',
            'name' => 'Test Category',
            'report_family' => 'rhp',
            'sales_analysis_bucket' => 'rhp',
            'sort_order' => 10,
            'is_active' => true,
        ]);
    }

    private function createSalesAnalysisUpload(ImportBatch $batch, string $status): UploadedFile
    {
        return UploadedFile::query()->create([
            'import_batch_id' => $batch->id,
            'file_type' => 'sales_analysis',
            'original_name' => 'Sales Analysis.xlsx',
            'storage_path' => 'uploads/'.$batch->id.'/sales_analysis-test.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'size_bytes' => 100,
            'sha256_checksum' => 'abc123',
            'status' => $status,
        ]);
    }

    private function createUnmatchedRow(ImportBatch $batch, UploadedFile $uploadedFile, string $itemId): SalesRow
    {
        return SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'uploaded_file_id' => $uploadedFile->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => 10,
            'source_bucket' => 'raw',
            'item_id' => $itemId,
            'description' => 'Test item',
            'classification_status' => 'unmatched',
            'amount' => 100,
        ]);
    }
}
