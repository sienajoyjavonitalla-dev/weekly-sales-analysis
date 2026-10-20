<?php

namespace App\Http\Controllers\Api;

use App\Domain\WeeklyAnalysis\Exports\Services\GeneratedWorkbookPreviewReader;
use App\Domain\WeeklyAnalysis\Exports\Services\WeeklyReportExportService;
use App\Domain\WeeklyAnalysis\Security\Services\AuditLogger;
use App\Models\GeneratedReport;
use App\Models\ImportBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GeneratedReportController
{
    public function index(ImportBatch $importBatch): JsonResponse
    {
        Gate::authorize('view', $importBatch);

        return response()->json([
            'data' => $importBatch->generatedReports()
                ->orderBy('report_type')
                ->get(),
        ]);
    }

    public function store(
        Request $request,
        ImportBatch $importBatch,
        WeeklyReportExportService $exportService,
        AuditLogger $auditLogger,
    ): JsonResponse {
        Gate::authorize('update', $importBatch);
        Gate::authorize('create', GeneratedReport::class);

        $validated = $request->validate([
            'include_state' => ['sometimes', 'boolean'],
        ]);

        $reports = $exportService->exportAll(
            importBatch: $importBatch,
            generatedByUserId: $request->user()?->id,
            includeState: (bool) ($validated['include_state'] ?? false),
        );
        $auditLogger->log('reports.generated', $request, $importBatch, $importBatch, [
            'report_ids' => collect($reports)->pluck('id')->all(),
        ]);

        return response()->json(['data' => $reports], 201);
    }

    public function preview(GeneratedReport $generatedReport, GeneratedWorkbookPreviewReader $previewReader): JsonResponse
    {
        Gate::authorize('view', $generatedReport);

        if ($generatedReport->report_type !== 'sales_analysis') {
            return response()->json(['message' => 'Only Sales Analysis reports can be viewed.'], 422);
        }

        if ($generatedReport->status !== 'completed' || $generatedReport->storage_path === null) {
            return response()->json(['message' => 'Report is not ready to view.'], 422);
        }

        $path = storage_path('app/private/'.$generatedReport->storage_path);

        if (! is_file($path)) {
            return response()->json(['message' => 'Generated report file was not found.'], 404);
        }

        $importBatch = $generatedReport->importBatch;

        return response()->json([
            'data' => [
                'report' => [
                    'id' => $generatedReport->id,
                    'report_type' => $generatedReport->report_type,
                    'file_name' => $generatedReport->file_name,
                    'generated_at' => $generatedReport->generated_at?->toIso8601String(),
                    'import_batch' => [
                        'id' => $importBatch->id,
                        'week_start' => $importBatch->week_start?->toDateString(),
                        'week_ending' => $importBatch->week_ending?->toDateString(),
                    ],
                ],
                'sheets' => $previewReader->read($path),
            ],
        ]);
    }

    public function download(Request $request, GeneratedReport $generatedReport, AuditLogger $auditLogger): BinaryFileResponse|JsonResponse
    {
        Gate::authorize('download', $generatedReport);

        if ($generatedReport->status !== 'completed' || $generatedReport->storage_path === null) {
            return response()->json(['message' => 'Report is not ready for download.'], 422);
        }

        $path = storage_path('app/private/'.$generatedReport->storage_path);

        if (! is_file($path)) {
            return response()->json(['message' => 'Generated report file was not found.'], 404);
        }

        $auditLogger->log('report.downloaded', $request, $generatedReport, $generatedReport->importBatch);

        return response()->download($path, $generatedReport->file_name);
    }
}
