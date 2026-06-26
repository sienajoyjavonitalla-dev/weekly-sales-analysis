<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'week_ending',
        'status',
        'created_by_user_id',
        'source_system',
        'currency',
        'notes',
        'metadata',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'week_ending' => 'date',
            'metadata' => 'array',
            'finalized_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function uploadedFiles(): HasMany
    {
        return $this->hasMany(UploadedFile::class);
    }

    public function salesRows(): HasMany
    {
        return $this->hasMany(SalesRow::class);
    }

    public function orderRows(): HasMany
    {
        return $this->hasMany(OrderRow::class);
    }

    public function incomeStatementLines(): HasMany
    {
        return $this->hasMany(IncomeStatementLine::class);
    }

    public function marketplaceFees(): HasMany
    {
        return $this->hasMany(MarketplaceFee::class);
    }

    public function reconciliationResult(): HasOne
    {
        return $this->hasOne(ReconciliationResult::class);
    }

    public function reportTotals(): HasMany
    {
        return $this->hasMany(ReportTotal::class);
    }

    public function generatedReports(): HasMany
    {
        return $this->hasMany(GeneratedReport::class);
    }
}
