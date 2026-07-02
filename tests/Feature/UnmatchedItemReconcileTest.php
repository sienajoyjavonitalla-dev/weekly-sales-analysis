<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ProductCategory;
use App\Models\SalesRow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnmatchedItemReconcileTest extends TestCase
{
    use RefreshDatabase;

    public function test_unmatched_item_groups_requires_authentication(): void
    {
        $batch = $this->createBatch($this->createAnalyst(), '2026-04-17');

        $this->getJson("/api/import-batches/{$batch->id}/unmatched-item-groups")
            ->assertUnauthorized();
    }

    public function test_unmatched_item_groups_returns_grouped_rows(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createBatch($user, '2026-04-17');

        $this->createUnmatchedRow($batch, 'ITEM-A', 'First item', 'CUST1');
        $this->createUnmatchedRow($batch, 'ITEM-A', 'First item', 'CUST2');
        $this->createUnmatchedRow($batch, 'ITEM-B', 'Second item', 'CUST3');

        $response = $this->actingAs($user)->getJson("/api/import-batches/{$batch->id}/unmatched-item-groups");

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.item_id', 'ITEM-A')
            ->assertJsonPath('data.0.row_count', 2)
            ->assertJsonPath('data.0.rows.0.customer_id', 'CUST1')
            ->assertJsonPath('data.1.item_id', 'ITEM-B')
            ->assertJsonPath('data.1.row_count', 1);
    }

    public function test_bulk_resolve_rejects_partial_resolutions(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createBatch($user, '2026-04-17');
        $category = $this->createCategory();

        $this->createUnmatchedRow($batch, 'ITEM-A', 'First item');
        $this->createUnmatchedRow($batch, 'ITEM-B', 'Second item');

        $response = $this->actingAs($user)->postJson("/api/import-batches/{$batch->id}/resolve-unmatched-items", [
            'resolutions' => [
                ['item_id' => 'ITEM-A', 'product_category_id' => $category->id],
            ],
        ]);

        $response->assertUnprocessable();
    }

    public function test_bulk_resolve_classifies_rows_and_creates_mapping_rules(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createBatch($user, '2026-04-17');
        $category = $this->createCategory();

        $this->createUnmatchedRow($batch, 'ITEM-A', 'First item');
        $this->createUnmatchedRow($batch, 'ITEM-A', 'First item duplicate');
        $this->createUnmatchedRow($batch, 'ITEM-B', 'Second item');

        $response = $this->actingAs($user)->postJson("/api/import-batches/{$batch->id}/resolve-unmatched-items", [
            'resolutions' => [
                ['item_id' => 'ITEM-A', 'product_category_id' => $category->id],
                ['item_id' => 'ITEM-B', 'product_category_id' => $category->id],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.resolved_item_count', 2)
            ->assertJsonPath('data.updated_row_count', 3)
            ->assertJsonPath('data.created_or_updated_rule_count', 2);

        $this->assertDatabaseCount('mapping_rules', 2);
        $this->assertDatabaseHas('mapping_rules', [
            'name' => 'resolved → ITEM-A',
            'match_field' => 'item_id',
            'match_operator' => 'exact',
            'pattern' => 'ITEM-A',
            'product_category_id' => $category->id,
        ]);

        $this->assertDatabaseMissing('sales_rows', [
            'import_batch_id' => $batch->id,
            'classification_status' => 'unmatched',
        ]);

        $this->assertDatabaseHas('sales_rows', [
            'import_batch_id' => $batch->id,
            'item_id' => 'ITEM-B',
            'classification_status' => 'manual',
            'product_category_id' => $category->id,
        ]);
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

    private function createBatch(User $user, string $weekEnding): ImportBatch
    {
        return ImportBatch::query()->create([
            'week_start' => date('Y-m-d', strtotime($weekEnding.' -6 days')),
            'week_ending' => $weekEnding,
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

    private function createUnmatchedRow(ImportBatch $batch, string $itemId, string $description, ?string $customerId = null): SalesRow
    {
        return SalesRow::query()->create([
            'import_batch_id' => $batch->id,
            'source_sheet' => 'Sheet',
            'source_row_number' => 10,
            'source_bucket' => 'raw',
            'item_id' => $itemId,
            'description' => $description,
            'customer_id' => $customerId,
            'classification_status' => 'unmatched',
            'amount' => 100,
        ]);
    }
}
