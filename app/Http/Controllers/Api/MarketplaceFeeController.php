<?php

namespace App\Http\Controllers\Api;

use App\Domain\WeeklyAnalysis\Security\Services\AuditLogger;
use App\Http\Requests\StoreMarketplaceFeeRequest;
use App\Models\ImportBatch;
use App\Models\MarketplaceFee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MarketplaceFeeController
{
    public function index(ImportBatch $importBatch): JsonResponse
    {
        Gate::authorize('view', $importBatch);

        $fees = MarketplaceFee::query()
            ->where('import_batch_id', $importBatch->id)
            ->orderBy('fee_date')
            ->orderBy('marketplace')
            ->get();

        return response()->json(['data' => $fees]);
    }

    public function store(StoreMarketplaceFeeRequest $request, ImportBatch $importBatch, AuditLogger $auditLogger): JsonResponse
    {
        Gate::authorize('create', MarketplaceFee::class);
        Gate::authorize('update', $importBatch);

        $fee = MarketplaceFee::query()->create([
            ...$request->validated(),
            'import_batch_id' => $importBatch->id,
            'created_by_user_id' => $request->user()?->id,
        ]);
        $auditLogger->log('marketplace_fee.created', $request, $fee, $importBatch);

        return response()->json(['data' => $fee], 201);
    }

    public function destroy(Request $request, MarketplaceFee $marketplaceFee, AuditLogger $auditLogger): JsonResponse
    {
        Gate::authorize('delete', $marketplaceFee);
        $auditLogger->log('marketplace_fee.deleted', $request, $marketplaceFee, $marketplaceFee->importBatch);
        $marketplaceFee->delete();

        return response()->json(null, 204);
    }
}
