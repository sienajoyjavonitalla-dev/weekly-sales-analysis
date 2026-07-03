<?php

namespace App\Http\Controllers\Api;

use App\Domain\WeeklyAnalysis\Imports\Services\UploadedFileDeletionService;
use App\Domain\WeeklyAnalysis\Security\Services\AuditLogger;
use App\Models\UploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UploadedFileController
{
    public function __construct(
        private readonly UploadedFileDeletionService $deletionService,
    ) {
    }

    public function download(UploadedFile $uploadedFile): BinaryFileResponse|JsonResponse
    {
        Gate::authorize('view', $uploadedFile);

        if ($uploadedFile->status === 'pending_reconcile') {
            return response()->json([
                'message' => 'Resolve unmatched items before downloading this Sales Analysis upload.',
            ], 422);
        }

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
    ): JsonResponse {
        Gate::authorize('delete', $uploadedFile);

        $this->deletionService->delete($request, $uploadedFile);

        return response()->json(['message' => 'Uploaded file deleted.']);
    }
}
