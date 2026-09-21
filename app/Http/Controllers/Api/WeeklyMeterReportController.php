<?php

namespace App\Http\Controllers\Api;

use App\Domain\WeeklyAnalysis\Reports\WeeklyMeterReportBuilder;
use App\Models\ImportBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WeeklyMeterReportController
{
    public function __invoke(Request $request, WeeklyMeterReportBuilder $builder): JsonResponse
    {
        Gate::authorize('viewAny', ImportBatch::class);

        $validated = $request->validate([
            'year' => ['sometimes', 'integer', 'min:2000', 'max:2100'],
            'month' => ['sometimes', 'integer', 'min:1', 'max:12'],
        ]);

        $year = (int) ($validated['year'] ?? now()->year);
        $month = (int) ($validated['month'] ?? now()->month);

        return response()->json([
            'data' => $builder->build($year, $month),
        ]);
    }
}
