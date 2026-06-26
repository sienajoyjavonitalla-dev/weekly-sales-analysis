<?php

namespace App\Domain\WeeklyAnalysis\Security\Services;

use App\Models\AuditLog;
use App\Models\ImportBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(
        string $action,
        ?Request $request = null,
        ?Model $auditable = null,
        ?ImportBatch $importBatch = null,
        array $properties = [],
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => $request?->user()?->id,
            'import_batch_id' => $importBatch?->id,
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'properties' => $properties,
        ]);
    }
}
