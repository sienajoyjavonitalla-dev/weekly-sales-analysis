<?php

namespace App\Http\Controllers\Api;

use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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
}
