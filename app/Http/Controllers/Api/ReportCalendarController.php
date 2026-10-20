<?php

namespace App\Http\Controllers\Api;

use App\Domain\WeeklyAnalysis\Reports\ReportCalendarBuilder;
use App\Models\ImportBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReportCalendarController
{
    public function __invoke(Request $request, ReportCalendarBuilder $builder): JsonResponse
    {
        Gate::authorize('viewAny', ImportBatch::class);

        $validated = $request->validate([
            'year' => ['sometimes', 'integer', 'min:2000', 'max:2100'],
            'month' => ['sometimes', 'integer', 'min:1', 'max:12'],
        ]);

        return response()->json([
            'data' => $builder->build(
                (int) ($validated['year'] ?? now()->year),
                (int) ($validated['month'] ?? now()->month),
            ),
        ]);
    }
}
