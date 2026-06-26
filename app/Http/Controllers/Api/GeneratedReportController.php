<?php

namespace App\Http\Controllers\Api;

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

        $reports = $exportService->exportAll(
            importBatch: $importBatch,
            generatedByUserId: $request->user()?->id,
        );
        $auditLogger->log('reports.generated', $request, $importBatch, $importBatch, [
            'report_ids' => collect($reports)->pluck('id')->all(),
        ]);

        return response()->json(['data' => $reports], 201);
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
