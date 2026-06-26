<?php

use App\Http\Controllers\Api\BatchClassificationController;
use App\Http\Controllers\Api\GeneratedReportController;
use App\Http\Controllers\Api\MappingRuleController;
use App\Http\Controllers\Api\MarketplaceFeeController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ReconciliationController;
use App\Http\Controllers\Api\ReportTotalController;
use App\Http\Controllers\Api\UnmatchedSalesRowController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/health', static fn (): JsonResponse => response()->json([
    'status' => 'ok',
    'service' => config('app.name'),
]));

Route::middleware(['auth', 'throttle:60,1'])->group(function (): void {
    Route::get('/product-categories', [ProductCategoryController::class, 'index']);

    Route::apiResource('mapping-rules', MappingRuleController::class)
        ->parameters(['mapping-rules' => 'mappingRule'])
        ->except(['show']);

    Route::post('/import-batches/{importBatch}/classify-sales-rows', BatchClassificationController::class);
    Route::get('/import-batches/{importBatch}/unmatched-sales-rows', [UnmatchedSalesRowController::class, 'index']);
    Route::get('/import-batches/{importBatch}/marketplace-fees', [MarketplaceFeeController::class, 'index']);
    Route::post('/import-batches/{importBatch}/marketplace-fees', [MarketplaceFeeController::class, 'store']);
    Route::get('/import-batches/{importBatch}/reconciliation', [ReconciliationController::class, 'show']);
    Route::post('/import-batches/{importBatch}/reconciliation', [ReconciliationController::class, 'store']);
    Route::get('/import-batches/{importBatch}/report-totals', [ReportTotalController::class, 'index']);
    Route::get('/import-batches/{importBatch}/generated-reports', [GeneratedReportController::class, 'index']);
    Route::post('/import-batches/{importBatch}/generated-reports', [GeneratedReportController::class, 'store']);
    Route::delete('/marketplace-fees/{marketplaceFee}', [MarketplaceFeeController::class, 'destroy']);
    Route::get('/generated-reports/{generatedReport}/download', [GeneratedReportController::class, 'download']);
    Route::patch('/sales-rows/{salesRow}/classification', [UnmatchedSalesRowController::class, 'update']);
});
