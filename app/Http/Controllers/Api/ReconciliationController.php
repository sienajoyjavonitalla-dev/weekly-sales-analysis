<?php

namespace App\Http\Controllers\Api;

use App\Domain\WeeklyAnalysis\Reconciliation\Services\WeeklyReconciliationService;
use App\Domain\WeeklyAnalysis\Security\Services\AuditLogger;
use App\Http\Requests\RunReconciliationRequest;
use App\Models\ImportBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ReconciliationController
{
    public function show(ImportBatch $importBatch): JsonResponse
    {
        Gate::authorize('view', $importBatch);

        return response()->json([
            'data' => $importBatch->reconciliationResult()
                ->with('approver')
                ->first(),
        ]);
    }

    public function store(
        RunReconciliationRequest $request,
        ImportBatch $importBatch,
        WeeklyReconciliationService $reconciliationService,
        AuditLogger $auditLogger,
    ): JsonResponse {
        Gate::authorize('update', $importBatch);

        $summary = $reconciliationService->reconcile(
            importBatch: $importBatch,
            tolerance: (float) $request->input('tolerance', 0.01),
        );
        $auditLogger->log('reconciliation.calculated', $request, $summary->result, $importBatch);

        return response()->json(['data' => $summary->toArray()]);
    }
}
