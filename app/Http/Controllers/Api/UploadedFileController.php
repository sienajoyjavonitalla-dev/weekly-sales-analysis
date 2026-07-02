<?php

namespace App\Http\Controllers\Api;

use App\Domain\WeeklyAnalysis\Security\Services\AuditLogger;
use App\Models\UploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UploadedFileController
{
    public function download(UploadedFile $uploadedFile): BinaryFileResponse|JsonResponse
    {
        Gate::authorize('view', $uploadedFile);

        if (! Storage::disk('local')->exists($uploadedFile->storage_path)) {
            return response()->json(['message' => 'Uploaded file was not found.'], 404);
        }

        return response()->download(
            Storage::disk('local')->path($uploadedFile->storage_path),
            $uploadedFile->original_name,
        );
    }

    public function destroy(
        Request $request,
        UploadedFile $uploadedFile,
        AuditLogger $auditLogger,
    ): JsonResponse {
        Gate::authorize('delete', $uploadedFile);

        $importBatch = $uploadedFile->importBatch;
        $properties = [
            'file_type' => $uploadedFile->file_type,
            'original_name' => $uploadedFile->original_name,
        ];

        if (Storage::disk('local')->exists($uploadedFile->storage_path)) {
            Storage::disk('local')->delete($uploadedFile->storage_path);
        }

        $uploadedFile->salesRows()->delete();
        $uploadedFile->orderRows()->delete();
        $uploadedFile->incomeStatementLines()->delete();
        $uploadedFile->delete();

        $auditLogger->log('uploaded_file.deleted', $request, $importBatch, $importBatch, $properties);

        if ($importBatch->status === 'draft' && ! $importBatch->uploadedFiles()->exists()) {
            if (Storage::disk('local')->exists('uploads/'.$importBatch->id)) {
                Storage::disk('local')->deleteDirectory('uploads/'.$importBatch->id);
            }

            $auditLogger->log('import_batch.deleted', $request, $importBatch, $importBatch, [
                'reason' => 'last_uploaded_file_removed',
            ]);

            $importBatch->delete();
        }

        return response()->json(['message' => 'Uploaded file deleted.']);
    }
}
