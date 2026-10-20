<?php

namespace App\Http\Controllers\Api;

use App\Domain\WeeklyAnalysis\Exports\Services\GeneratedReportRefresher;
use App\Domain\WeeklyAnalysis\Security\Services\AuditLogger;
use App\Http\Requests\StoreMappingRuleRequest;
use App\Http\Requests\UpdateMappingRuleRequest;
use App\Models\MappingRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MappingRuleController
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', MappingRule::class);

        $sourceType = $request->string('source_type')->toString();

        $rules = MappingRule::query()
            ->with('productCategory')
            ->when($request->boolean('active_only'), fn ($query) => $query->where('is_active', true))
            ->when($sourceType !== '', fn ($query) => $query->where('source_type', $sourceType))
            ->orderBy('priority')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $rules]);
    }

    public function store(
        StoreMappingRuleRequest $request,
        AuditLogger $auditLogger,
        GeneratedReportRefresher $refresher,
    ): JsonResponse {
        Gate::authorize('create', MappingRule::class);

        $rule = MappingRule::query()->create($request->validated());
        $auditLogger->log('mapping_rule.created', $request, $rule);

        return response()->json([
            'data' => $rule->load('productCategory'),
            'meta' => [
                // The Unmatched Items flow creates rules mid-upload and re-classifies its own batch,
                // so it opts out of refreshing every previously generated report.
                'reports_refreshed' => $request->boolean('skip_report_refresh')
                    ? 0
                    : $refresher->refreshAll($request->user()?->id),
            ],
        ], 201);
    }

    public function update(
        UpdateMappingRuleRequest $request,
        MappingRule $mappingRule,
        AuditLogger $auditLogger,
        GeneratedReportRefresher $refresher,
    ): JsonResponse {
        Gate::authorize('update', $mappingRule);

        $mappingRule->update($request->validated());
        $auditLogger->log('mapping_rule.updated', $request, $mappingRule);

        $changed = $mappingRule->wasChanged();

        return response()->json([
            'data' => $mappingRule->load('productCategory'),
            'meta' => [
                'reports_refreshed' => $changed ? $refresher->refreshAll($request->user()?->id) : 0,
            ],
        ]);
    }

    public function destroy(
        Request $request,
        MappingRule $mappingRule,
        AuditLogger $auditLogger,
        GeneratedReportRefresher $refresher,
    ): JsonResponse {
        Gate::authorize('delete', $mappingRule);
        $auditLogger->log('mapping_rule.deleted', $request, $mappingRule);
        $mappingRule->delete();

        return response()->json([
            'meta' => ['reports_refreshed' => $refresher->refreshAll($request->user()?->id)],
        ]);
    }
}
