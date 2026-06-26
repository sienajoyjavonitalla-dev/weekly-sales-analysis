<?php

namespace App\Http\Controllers\Api;

use App\Domain\WeeklyAnalysis\Classification\Services\SalesRowClassifier;
use App\Domain\WeeklyAnalysis\Security\Services\AuditLogger;
use App\Models\ImportBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BatchClassificationController
{
    public function __invoke(
        Request $request,
        ImportBatch $importBatch,
        SalesRowClassifier $classifier,
        AuditLogger $auditLogger,
    ): JsonResponse {
        Gate::authorize('update', $importBatch);

        $summary = $classifier->classifyBatch($importBatch);
        $auditLogger->log('sales_rows.classified', $request, $importBatch, $importBatch, $summary);

        return response()->json([
            'data' => [
                'import_batch_id' => $importBatch->id,
                ...$summary,
            ],
        ]);
    }
}
