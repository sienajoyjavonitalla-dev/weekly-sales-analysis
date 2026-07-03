<?php

namespace App\Domain\WeeklyAnalysis\Imports\Services;

use App\Domain\WeeklyAnalysis\Security\Services\AuditLogger;
use App\Models\ImportBatch;
use App\Models\UploadedFile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadedFileDeletionService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function delete(Request $request, UploadedFile $uploadedFile, string $reason = 'manual_delete'): void
    {
        $importBatch = $uploadedFile->importBatch;
        $properties = [
            'file_type' => $uploadedFile->file_type,
            'original_name' => $uploadedFile->original_name,
            'reason' => $reason,
        ];

        if (Storage::disk('local')->exists($uploadedFile->storage_path)) {
            Storage::disk('local')->delete($uploadedFile->storage_path);
        }

        $uploadedFile->salesRows()->delete();
        $uploadedFile->orderRows()->delete();
        $uploadedFile->incomeStatementLines()->delete();
        $uploadedFile->delete();

        $this->auditLogger->log('uploaded_file.deleted', $request, $importBatch, $importBatch, $properties);

        if ($importBatch !== null && $importBatch->status === 'draft' && ! $importBatch->uploadedFiles()->exists()) {
            if (Storage::disk('local')->exists('uploads/'.$importBatch->id)) {
                Storage::disk('local')->deleteDirectory('uploads/'.$importBatch->id);
            }

            $this->auditLogger->log('import_batch.deleted', $request, $importBatch, $importBatch, [
                'reason' => 'last_uploaded_file_removed',
            ]);

            $importBatch->delete();
        }
    }

    /**
     * @return int Number of discarded pending reconcile uploads.
     */
    public function discardPendingSalesAnalysisReconciles(Request $request, User $user): int
    {
        $files = UploadedFile::query()
            ->with('importBatch')
            ->where('file_type', 'sales_analysis')
            ->where('status', 'pending_reconcile')
            ->whereHas('importBatch', function ($query) use ($user): void {
                $query
                    ->where('status', 'draft')
                    ->where('created_by_user_id', $user->id);
            })
            ->get();

        foreach ($files as $uploadedFile) {
            $this->delete($request, $uploadedFile, 'pending_reconcile_abandoned');
        }

        return $files->count();
    }

    public function confirmSalesAnalysisReconcile(ImportBatch $importBatch): void
    {
        $importBatch->uploadedFiles()
            ->where('file_type', 'sales_analysis')
            ->where('status', 'pending_reconcile')
            ->update(['status' => 'imported']);
    }
}
