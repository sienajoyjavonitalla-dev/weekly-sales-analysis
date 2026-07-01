<?php

namespace App\Http\Controllers\Api;

use App\Models\ImportBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ImportBatchController
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', ImportBatch::class);

        $batches = ImportBatch::query()
            ->with(['uploadedFiles' => fn ($query) => $query->orderBy('file_type')])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('week_ending'), fn ($query) => $query->whereDate('week_ending', $request->string('week_ending')->toString()))
            ->orderByDesc('week_ending')
            ->orderByDesc('id')
            ->get()
            ->map(fn (ImportBatch $batch) => $this->batchPayload($batch));

        return response()->json(['data' => $batches]);
    }

    public function show(ImportBatch $importBatch): JsonResponse
    {
        Gate::authorize('view', $importBatch);

        $importBatch->load(['uploadedFiles' => fn ($query) => $query->orderBy('file_type')]);

        return response()->json([
            'data' => $this->batchPayload($importBatch),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function batchPayload(ImportBatch $batch): array
    {
        return [
            'id' => $batch->id,
            'week_start' => $batch->week_start?->toDateString(),
            'week_ending' => $batch->week_ending?->toDateString(),
            'status' => $batch->status,
            'created_at' => $batch->created_at?->toIso8601String(),
            'finalized_at' => $batch->finalized_at?->toIso8601String(),
            'uploaded_file_count' => $batch->uploadedFiles->count(),
            'uploaded_files' => $batch->uploadedFiles->map(fn ($file) => [
                'id' => $file->id,
                'file_type' => $file->file_type,
                'original_name' => $file->original_name,
                'status' => $file->status,
                'validation_errors' => $file->validation_errors,
                'updated_at' => $file->updated_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }
}
