<?php

namespace App\Http\Controllers\Api;

use App\Domain\WeeklyAnalysis\Security\Services\AuditLogger;
use App\Http\Requests\ResolveSalesRowClassificationRequest;
use App\Models\ImportBatch;
use App\Models\SalesRow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UnmatchedSalesRowController
{
    public function index(Request $request, ImportBatch $importBatch): JsonResponse
    {
        Gate::authorize('view', $importBatch);

        $rows = SalesRow::query()
            ->with(['productCategory', 'mappingRule'])
            ->where('import_batch_id', $importBatch->id)
            ->where('classification_status', 'unmatched')
            ->when($request->string('search')->isNotEmpty(), function ($query) use ($request): void {
                $search = '%'.$request->string('search')->toString().'%';

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('item_id', 'like', $search)
                        ->orWhere('description', 'like', $search)
                        ->orWhere('customer_name', 'like', $search)
                        ->orWhere('invoice_number', 'like', $search);
                });
            })
            ->orderBy('source_sheet')
            ->orderBy('source_row_number')
            ->paginate((int) $request->integer('per_page', 50));

        return response()->json($rows);
    }

    public function update(
        ResolveSalesRowClassificationRequest $request,
        SalesRow $salesRow,
        AuditLogger $auditLogger,
    ): JsonResponse
    {
        Gate::authorize('update', $salesRow->importBatch);

        $salesRow->forceFill([
            'product_category_id' => $request->integer('product_category_id'),
            'mapping_rule_id' => null,
            'source_bucket' => $request->string('source_bucket')->toString(),
            'classification_status' => 'manual',
            'classified_by_user_id' => $request->user()?->id,
            'classified_at' => now(),
            'classification_notes' => $request->validated('notes'),
        ])->save();
        $auditLogger->log('sales_row.manually_classified', $request, $salesRow, $salesRow->importBatch);

        return response()->json([
            'data' => $salesRow->load(['productCategory', 'mappingRule', 'classifier']),
        ]);
    }
}
