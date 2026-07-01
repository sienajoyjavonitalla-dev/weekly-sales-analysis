<?php

namespace App\Http\Controllers\Api;

use App\Domain\WeeklyAnalysis\Imports\Services\WorkbookImportService;
use App\Domain\WeeklyAnalysis\Security\Services\AuditLogger;
use App\Http\Requests\UploadWeeklyWorkbookSetRequest;
use App\Models\ImportBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class WorkbookImportController
{
    public function __invoke(
        UploadWeeklyWorkbookSetRequest $request,
        WorkbookImportService $importService,
        AuditLogger $auditLogger,
    ): JsonResponse {
        $importBatch = $request->filled('import_batch_id')
            ? ImportBatch::query()->findOrFail($request->integer('import_batch_id'))
            : null;

        if ($importBatch !== null) {
            Gate::authorize('update', $importBatch);
        } else {
            Gate::authorize('create', ImportBatch::class);
        }

        $files = [];

        foreach (config('weekly-analysis.uploads.required_file_types') as $type) {
            if ($request->hasFile($type)) {
                $files[$type] = $request->file($type);
            }
        }

        $result = $importService->import(
            files: $files,
            importBatch: $importBatch,
            weekStart: $request->input('week_start'),
            weekEnding: $request->input('week_ending'),
            userId: $request->user()?->id,
        );

        $auditLogger->log(
            action: 'workbooks.imported',
            request: $request,
            auditable: $result['import_batch'],
            importBatch: $result['import_batch'],
            properties: [
                'file_types' => array_keys($files),
                'summary' => $result['summary'],
            ],
        );

        return response()->json(['data' => $result], 201);
    }
}
