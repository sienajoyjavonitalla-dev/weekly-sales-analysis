<?php

namespace App\Http\Controllers\Api;

use App\Domain\WeeklyAnalysis\Exports\Services\GeneratedReportRefresher;
use App\Domain\WeeklyAnalysis\Security\Services\AuditLogger;
use App\Http\Requests\StoreProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ProductCategoryController
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', ProductCategory::class);

        $categories = ProductCategory::query()
            ->when($request->boolean('active_only', true), fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $categories]);
    }

    public function store(StoreProductCategoryRequest $request, AuditLogger $auditLogger): JsonResponse
    {
        Gate::authorize('create', ProductCategory::class);

        $payload = $this->payloadWithGeneratedFields($request->validated());
        $category = ProductCategory::query()->create($payload);
        $auditLogger->log('product_category.created', $request, $category);

        // A new category is not used by any rule or sales row yet, so no report changes.
        return response()->json(['data' => $category], 201);
    }

    public function update(
        UpdateProductCategoryRequest $request,
        ProductCategory $productCategory,
        AuditLogger $auditLogger,
        GeneratedReportRefresher $refresher,
    ): JsonResponse {
        Gate::authorize('update', $productCategory);

        $payload = $this->payloadWithGeneratedFields($request->validated(), $productCategory);
        $productCategory->update($payload);
        $auditLogger->log('product_category.updated', $request, $productCategory);

        $changed = $productCategory->wasChanged();

        return response()->json([
            'data' => $productCategory->refresh(),
            'meta' => [
                'reports_refreshed' => $changed ? $refresher->refreshAll($request->user()?->id) : 0,
            ],
        ]);
    }

    public function destroy(
        Request $request,
        ProductCategory $productCategory,
        AuditLogger $auditLogger,
        GeneratedReportRefresher $refresher,
    ): JsonResponse {
        Gate::authorize('delete', $productCategory);

        $auditLogger->log('product_category.deleted', $request, $productCategory);
        $productCategory->delete();

        return response()->json([
            'meta' => ['reports_refreshed' => $refresher->refreshAll($request->user()?->id)],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function payloadWithGeneratedFields(array $payload, ?ProductCategory $productCategory = null): array
    {
        if (array_key_exists('name', $payload)) {
            $payload['code'] = $this->uniqueCodeFromName(
                name: (string) $payload['name'],
                ignoreId: $productCategory?->id,
            );
        }

        $bucket = $payload['sales_analysis_bucket']
            ?? $productCategory?->sales_analysis_bucket
            ?? 'general';

        $payload['report_family'] = $bucket ?: 'general';

        return $payload;
    }

    private function uniqueCodeFromName(string $name, ?int $ignoreId = null): string
    {
        $baseCode = Str::of($name)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString() ?: 'category';
        $code = $baseCode;
        $suffix = 2;

        while (ProductCategory::query()
            ->where('code', $code)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()
        ) {
            $code = "{$baseCode}_{$suffix}";
            $suffix++;
        }

        return $code;
    }
}
