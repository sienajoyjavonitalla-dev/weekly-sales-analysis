<?php

namespace App\Http\Controllers\Api;

use App\Domain\WeeklyAnalysis\Reports\WeeklyMeterReportBuilder;
use App\Domain\WeeklyAnalysis\Security\Services\AuditLogger;
use App\Http\Requests\UpdateWeeklyMeterAdditionalFeesRequest;
use App\Models\ImportBatch;
use App\Models\WeeklyMeterAdditionalFee;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class WeeklyMeterAdditionalFeeController
{
    public function update(
        UpdateWeeklyMeterAdditionalFeesRequest $request,
        WeeklyMeterReportBuilder $builder,
        AuditLogger $auditLogger,
    ): JsonResponse {
        Gate::authorize('viewAny', ImportBatch::class);

        $validated = $request->validated();
        $year = (int) $validated['year'];
        $month = (int) $validated['month'];
        $modelLabel = (string) $validated['model_label'];
        $fees = $validated['fees'];

        DB::transaction(function () use ($year, $month, $modelLabel, $fees, $request): void {
            WeeklyMeterAdditionalFee::query()
                ->where('year', $year)
                ->where('month', $month)
                ->where('model_label', $modelLabel)
                ->delete();

            foreach (array_values($fees) as $index => $fee) {
                WeeklyMeterAdditionalFee::query()->create([
                    'year' => $year,
                    'month' => $month,
                    'model_label' => $modelLabel,
                    'amount' => $fee['amount'],
                    'sort_order' => $index,
                    'created_by_user_id' => $request->user()?->id,
                ]);
            }
        });

        $auditLogger->log('weekly_meter_additional_fees.updated', $request, null, null, [
            'year' => $year,
            'month' => $month,
            'model_label' => $modelLabel,
            'fee_count' => count($fees),
        ]);

        return response()->json([
            'data' => $builder->build($year, $month),
        ]);
    }
}
