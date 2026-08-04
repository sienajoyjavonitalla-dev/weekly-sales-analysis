<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BatchClassificationController;
use App\Http\Controllers\Api\GeneratedReportController;
use App\Http\Controllers\Api\ImportBatchController;
use App\Http\Controllers\Api\MappingRuleController;
use App\Http\Controllers\Api\MarketplaceFeeController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ReconciliationController;
use App\Http\Controllers\Api\ReportTotalController;
use App\Http\Controllers\Api\UnmatchedSalesRowController;
use App\Http\Controllers\Api\UploadedFileController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorkbookImportController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/health', static fn (): JsonResponse => response()->json([
    'status' => 'ok',
    'service' => config('app.name'),
]));

Route::middleware(['web'])->group(function (): void {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware(['web', 'auth', 'throttle:60,1'])->group(function (): void {
    Route::patch('/me/theme', [AuthController::class, 'updateTheme']);
    Route::post('/me/profile-photo', [AuthController::class, 'updateProfilePhoto']);
    Route::patch('/me/password', [AuthController::class, 'updatePassword']);
    Route::post('/workbook-imports', WorkbookImportController::class);

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::patch('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);

    Route::apiResource('product-categories', ProductCategoryController::class)
        ->parameters(['product-categories' => 'productCategory'])
        ->except(['show']);

    Route::apiResource('mapping-rules', MappingRuleController::class)
        ->parameters(['mapping-rules' => 'mappingRule'])
        ->except(['show']);

    Route::get('/import-batches', [ImportBatchController::class, 'index']);
    Route::post('/import-batches/discard-pending-reconciles', [ImportBatchController::class, 'discardPendingReconciles']);
    Route::get('/import-batches/{importBatch}', [ImportBatchController::class, 'show']);

    Route::post('/import-batches/{importBatch}/classify-sales-rows', BatchClassificationController::class);
    Route::get('/import-batches/{importBatch}/unmatched-sales-rows', [UnmatchedSalesRowController::class, 'index']);
    Route::get('/import-batches/{importBatch}/unmatched-item-groups', [UnmatchedSalesRowController::class, 'itemGroups']);
    Route::post('/import-batches/{importBatch}/resolve-unmatched-items', [UnmatchedSalesRowController::class, 'bulkResolve']);
    Route::get('/import-batches/{importBatch}/marketplace-fees', [MarketplaceFeeController::class, 'index']);
    Route::post('/import-batches/{importBatch}/marketplace-fees', [MarketplaceFeeController::class, 'store']);
    Route::get('/import-batches/{importBatch}/reconciliation', [ReconciliationController::class, 'show']);
    Route::post('/import-batches/{importBatch}/reconciliation', [ReconciliationController::class, 'store']);
    Route::get('/import-batches/{importBatch}/report-totals', [ReportTotalController::class, 'index']);
    Route::get('/import-batches/{importBatch}/generated-reports', [GeneratedReportController::class, 'index']);
    Route::post('/import-batches/{importBatch}/generated-reports', [GeneratedReportController::class, 'store']);
    Route::delete('/marketplace-fees/{marketplaceFee}', [MarketplaceFeeController::class, 'destroy']);
    Route::get('/uploaded-files/{uploadedFile}/download', [UploadedFileController::class, 'download']);
    Route::delete('/uploaded-files/{uploadedFile}', [UploadedFileController::class, 'destroy']);
    Route::get('/generated-reports/{generatedReport}/download', [GeneratedReportController::class, 'download']);
    Route::patch('/sales-rows/{salesRow}/classification', [UnmatchedSalesRowController::class, 'update']);
});
