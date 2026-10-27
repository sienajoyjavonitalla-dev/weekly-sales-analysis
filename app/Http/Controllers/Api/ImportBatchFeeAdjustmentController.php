<?php

namespace App\Http\Controllers\Api;

use App\Domain\WeeklyAnalysis\Exports\Services\WeeklyReportExportService;
use App\Domain\WeeklyAnalysis\Imports\Enums\WorkbookType;
use App\Domain\WeeklyAnalysis\Security\Services\AuditLogger;
use App\Http\Requests\UpdateImportBatchFeeAdjustmentsRequest;
use App\Models\ImportBatch;
use App\Models\ImportBatchFeeAdjustment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ImportBatchFeeAdjustmentController
{
    public function index(Request $request, ImportBatch $importBatch): JsonResponse
    {
        Gate::authorize('view', $importBatch);

        $marketplace = (string) $request->query('marketplace', '');

        $fees = $importBatch->feeAdjustments()
            ->when(
                in_array($marketplace, ['amazon', 'walmart'], true),
                fn ($query) => $query->where('marketplace', $marketplace),
            )
            ->orderBy('marketplace')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $fees,
            'totals' => [
                'amazon' => round((float) $importBatch->feeAdjustments()->where('marketplace', 'amazon')->sum('amount'), 2),
                'walmart' => round((float) $importBatch->feeAdjustments()->where('marketplace', 'walmart')->sum('amount'), 2),
            ],
        ]);
    }

    public function update(
        UpdateImportBatchFeeAdjustmentsRequest $request,
        ImportBatch $importBatch,
        WeeklyReportExportService $exportService,
        AuditLogger $auditLogger,
    ): JsonResponse {
        Gate::authorize('view', $importBatch);

        $validated = $request->validated();
        $marketplace = (string) $validated['marketplace'];
        $fees = $validated['fees'];

        DB::transaction(function () use ($importBatch, $marketplace, $fees, $request): void {
            ImportBatchFeeAdjustment::query()
                ->where('import_batch_id', $importBatch->id)
                ->where('marketplace', $marketplace)
                ->delete();

            foreach (array_values($fees) as $index => $fee) {
                ImportBatchFeeAdjustment::query()->create([
                    'import_batch_id' => $importBatch->id,
                    'marketplace' => $marketplace,
                    'amount' => $fee['amount'],
                    'sort_order' => $index,
                    'created_by_user_id' => $request->user()?->id,
                ]);
            }
        });

        $workbookType = $marketplace === 'amazon' ? WorkbookType::AmazonFees : WorkbookType::WalmartFees;
        $report = $exportService->exportMarketplaceFees(
            $importBatch->refresh(),
            $workbookType,
            $request->user()?->id,
        );

        $auditLogger->log('import_batch.fee_adjustments.updated', $request, $importBatch, $importBatch, [
            'marketplace' => $marketplace,
            'fee_count' => count($fees),
        ]);

        return response()->json([
            'data' => [
                'fees' => $importBatch->feeAdjustments()
                    ->where('marketplace', $marketplace)
                    ->orderBy('sort_order')
                    ->get(),
                'total' => round((float) $importBatch->feeAdjustments()->where('marketplace', $marketplace)->sum('amount'), 2),
                'report' => $report,
            ],
        ]);
    }
}
