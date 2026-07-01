<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\UploadedFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportBatchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_batches_index_requires_authentication(): void
    {
        $this->getJson('/api/import-batches')->assertUnauthorized();
    }

    public function test_analyst_can_list_import_batches_with_uploaded_files(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createBatch($user, '2026-04-17');

        UploadedFile::query()->create([
            'import_batch_id' => $batch->id,
            'file_type' => 'sales_analysis',
            'original_name' => '04-17-2026 Sales Analysis.xlsx',
            'storage_path' => 'uploads/'.$batch->id.'/sales_analysis.xlsx',
            'sha256_checksum' => str_repeat('a', 64),
            'status' => 'imported',
        ]);

        $response = $this->actingAs($user)->getJson('/api/import-batches');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $batch->id)
            ->assertJsonPath('data.0.week_ending', '2026-04-17')
            ->assertJsonPath('data.0.status', 'draft')
            ->assertJsonPath('data.0.uploaded_file_count', 1)
            ->assertJsonPath('data.0.uploaded_files.0.file_type', 'sales_analysis')
            ->assertJsonPath('data.0.uploaded_files.0.original_name', '04-17-2026 Sales Analysis.xlsx')
            ->assertJsonPath('data.0.uploaded_files.0.status', 'imported');
    }

    public function test_batches_are_sorted_by_week_ending_desc(): void
    {
        $user = $this->createAnalyst();

        $olderBatch = $this->createBatch($user, '2026-04-10');
        $newerBatch = $this->createBatch($user, '2026-04-17', 'Traverse Global 2');

        $response = $this->actingAs($user)->getJson('/api/import-batches');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $newerBatch->id)
            ->assertJsonPath('data.1.id', $olderBatch->id);
    }

    public function test_can_filter_batches_by_status(): void
    {
        $user = $this->createAnalyst();

        $draftBatch = $this->createBatch($user, '2026-04-10');
        ImportBatch::query()->create([
            'week_start' => '2026-04-11',
            'week_ending' => '2026-04-17',
            'status' => 'finalized',
            'created_by_user_id' => $user->id,
            'source_system' => 'Traverse Global 2',
        ]);

        $response = $this->actingAs($user)->getJson('/api/import-batches?status=draft');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $draftBatch->id);
    }

    public function test_analyst_can_show_single_import_batch(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createBatch($user, '2026-04-17');

        UploadedFile::query()->create([
            'import_batch_id' => $batch->id,
            'file_type' => 'income_statement',
            'original_name' => '04-17-2026 Income Statement.xlsx',
            'storage_path' => 'uploads/'.$batch->id.'/income_statement.xlsx',
            'sha256_checksum' => str_repeat('b', 64),
            'status' => 'imported',
        ]);

        $response = $this->actingAs($user)->getJson('/api/import-batches/'.$batch->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $batch->id)
            ->assertJsonPath('data.uploaded_file_count', 1)
            ->assertJsonPath('data.uploaded_files.0.file_type', 'income_statement');
    }

    public function test_show_import_batch_requires_authentication(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createBatch($user, '2026-04-17');

        $this->getJson('/api/import-batches/'.$batch->id)->assertUnauthorized();
    }

    public function test_authenticated_analyst_can_access_batch_scoped_review_endpoint(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createBatch($user, '2026-04-17');

        $this->actingAs($user)
            ->getJson('/api/import-batches/'.$batch->id.'/unmatched-sales-rows')
            ->assertOk();
    }

    public function test_authenticated_analyst_can_access_batch_scoped_reconciliation_endpoint(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createBatch($user, '2026-04-17');

        $this->actingAs($user)
            ->getJson('/api/import-batches/'.$batch->id.'/reconciliation')
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_authenticated_analyst_can_access_batch_scoped_exports_endpoint(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createBatch($user, '2026-04-17');

        $this->actingAs($user)
            ->getJson('/api/import-batches/'.$batch->id.'/generated-reports')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_analyst_can_delete_uploaded_file_from_own_draft_batch(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createBatch($user, '2026-04-17');

        $uploadedFile = UploadedFile::query()->create([
            'import_batch_id' => $batch->id,
            'file_type' => 'sales_analysis',
            'original_name' => '04-17-2026 Sales Analysis.xlsx',
            'storage_path' => 'uploads/'.$batch->id.'/sales_analysis.xlsx',
            'sha256_checksum' => str_repeat('a', 64),
            'status' => 'imported',
        ]);

        $this->actingAs($user)
            ->deleteJson('/api/uploaded-files/'.$uploadedFile->id)
            ->assertOk()
            ->assertJsonPath('message', 'Uploaded file deleted.');

        $this->assertDatabaseMissing('uploaded_files', ['id' => $uploadedFile->id]);
    }

    public function test_analyst_cannot_delete_uploaded_file_from_finalized_batch(): void
    {
        $user = $this->createAnalyst();
        $batch = ImportBatch::query()->create([
            'week_ending' => '2026-04-17',
            'status' => 'finalized',
            'created_by_user_id' => $user->id,
            'source_system' => 'Traverse Global',
        ]);

        $uploadedFile = UploadedFile::query()->create([
            'import_batch_id' => $batch->id,
            'file_type' => 'sales_analysis',
            'original_name' => '04-17-2026 Sales Analysis.xlsx',
            'storage_path' => 'uploads/'.$batch->id.'/sales_analysis.xlsx',
            'sha256_checksum' => str_repeat('a', 64),
            'status' => 'imported',
        ]);

        $this->actingAs($user)
            ->deleteJson('/api/uploaded-files/'.$uploadedFile->id)
            ->assertForbidden();

        $this->assertDatabaseHas('uploaded_files', ['id' => $uploadedFile->id]);
    }

    public function test_delete_uploaded_file_requires_authentication(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createBatch($user, '2026-04-17');

        $uploadedFile = UploadedFile::query()->create([
            'import_batch_id' => $batch->id,
            'file_type' => 'sales_analysis',
            'original_name' => '04-17-2026 Sales Analysis.xlsx',
            'storage_path' => 'uploads/'.$batch->id.'/sales_analysis.xlsx',
            'sha256_checksum' => str_repeat('a', 64),
            'status' => 'imported',
        ]);

        $this->deleteJson('/api/uploaded-files/'.$uploadedFile->id)->assertUnauthorized();
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

    private function createBatch(User $user, string $weekEnding, string $sourceSystem = 'Traverse Global'): ImportBatch
    {
        return ImportBatch::query()->create([
            'week_start' => date('Y-m-d', strtotime($weekEnding.' -6 days')),
            'week_ending' => $weekEnding,
            'status' => 'draft',
            'created_by_user_id' => $user->id,
            'source_system' => $sourceSystem,
        ]);
    }
}
