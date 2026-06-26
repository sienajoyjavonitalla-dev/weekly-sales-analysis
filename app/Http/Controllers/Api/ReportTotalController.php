<?php

namespace App\Http\Controllers\Api;

use App\Models\ImportBatch;
use App\Models\ReportTotal;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ReportTotalController
{
    public function index(ImportBatch $importBatch): JsonResponse
    {
        Gate::authorize('view', $importBatch);

        $totals = ReportTotal::query()
            ->where('import_batch_id', $importBatch->id)
            ->orderBy('report_type')
            ->orderBy('metric_key')
            ->get();

        return response()->json(['data' => $totals]);
    }
}
