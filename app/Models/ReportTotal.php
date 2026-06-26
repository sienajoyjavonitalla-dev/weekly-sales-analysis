<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportTotal extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_batch_id',
        'report_type',
        'period_label',
        'metric_key',
        'quantity',
        'amount',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }
}
