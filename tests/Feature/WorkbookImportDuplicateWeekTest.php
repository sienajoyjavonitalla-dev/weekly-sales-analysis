<?php

namespace Tests\Feature;

use App\Domain\WeeklyAnalysis\Imports\Services\UploadedFileDeletionService;
use App\Domain\WeeklyAnalysis\Imports\Services\WorkbookImportService;
use App\Models\ImportBatch;
use App\Models\UploadedFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkbookImportDuplicateWeekTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_week_with_uploaded_files_returns_friendly_validation_error(): void
    {
        $user = $this->createAnalyst();
        $batch = $this->createDraftBatch($user, '2026-08-23');
        $this->createSalesAnalysisFile($batch);

        try {
            app(WorkbookImportService::class)->import(
                files: ['sales_analysis' => $this->fakeWorkbook()],
                importBatch: null,
                weekStart: '2026-08-17',
                weekEnding: '2026-08-23',
                userId: $user->id,
            );

            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'This week was already uploaded. Open the existing batch for that week, or choose a different week.',
                $exception->errors()['week_ending'][0] ?? null,
            );
        }

        $this->assertDatabaseHas('import_batches', ['id' => $batch->id]);
    }

    public function test_empty_draft_batch_is_deleted_and_does_not_count_as_a_duplicate(): void
    {
        $user = $this->createAnalyst();
        $emptyBatch = $this->createDraftBatch($user, '2026-04-17');

        try {
            app(WorkbookImportService::class)->import(
                files: [],
                importBatch: null,
                weekStart: '2026-04-11',
                weekEnding: '2026-04-17',
                userId: $user->id,
            );

            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Upload at least one workbook.',
                $exception->errors()['workbooks'][0] ?? null,
            );
        }

        $this->assertDatabaseMissing('import_batches', ['id' => $emptyBatch->id]);
        $this->assertSame(0, ImportBatch::query()->count());
    }

    public function test_delete_empty_draft_batches_removes_drafts_without_files(): void
    {
        $user = $this->createAnalyst();
        $emptyBatch = $this->createDraftBatch($user, '2026-04-17');
        $keptBatch = $this->createDraftBatch($user, '2026-04-10', 'Traverse Global 2');
        $this->createSalesAnalysisFile($keptBatch);

        $deleted = app(UploadedFileDeletionService::class)->deleteEmptyDraftBatches();

        $this->assertSame(1, $deleted);
        $this->assertDatabaseMissing('import_batches', ['id' => $emptyBatch->id]);
        $this->assertDatabaseHas('import_batches', ['id' => $keptBatch->id]);
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

    private function createDraftBatch(User $user, string $weekEnding, string $sourceSystem = 'Traverse Global'): ImportBatch
    {
        return ImportBatch::query()->create([
            'week_start' => date('Y-m-d', strtotime($weekEnding.' -6 days')),
            'week_ending' => $weekEnding,
            'status' => 'draft',
            'created_by_user_id' => $user->id,
            'source_system' => $sourceSystem,
        ]);
    }

    private function createSalesAnalysisFile(ImportBatch $batch): UploadedFile
    {
        return UploadedFile::query()->create([
            'import_batch_id' => $batch->id,
            'file_type' => 'sales_analysis',
            'original_name' => 'Sales Analysis.xlsx',
            'storage_path' => 'uploads/'.$batch->id.'/sales_analysis.xlsx',
            'sha256_checksum' => str_repeat('a', 64),
            'status' => 'imported',
        ]);
    }

    private function fakeWorkbook(): \Illuminate\Http\UploadedFile
    {
        return \Illuminate\Http\UploadedFile::fake()->create('04-17-2026 Sales Analysis.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
