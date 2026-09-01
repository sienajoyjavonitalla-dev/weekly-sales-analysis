<?php

namespace Tests\Feature;

use App\Domain\WeeklyAnalysis\Imports\Services\WorkbookImportService;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkbookImportDuplicateWeekTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_week_upload_returns_friendly_validation_error(): void
    {
        $user = User::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lyst',
            'email' => 'analyst@example.com',
            'password' => 'password',
            'role' => 'analyst',
        ]);

        ImportBatch::query()->create([
            'week_start' => '2026-08-17',
            'week_ending' => '2026-08-23',
            'status' => 'draft',
            'created_by_user_id' => $user->id,
            'source_system' => 'Traverse Global',
        ]);

        try {
            app(WorkbookImportService::class)->import(
                files: [],
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
    }
}
