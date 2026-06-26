<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_batch_id',
        'sales_analysis_total',
        'income_statement_total',
        'marketplace_fee_total',
        'adjusted_income_statement_total',
        'difference',
        'is_balanced',
        'tolerance',
        'category_totals',
        'messages',
        'approved_by_user_id',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'sales_analysis_total' => 'decimal:2',
            'income_statement_total' => 'decimal:2',
            'marketplace_fee_total' => 'decimal:2',
            'adjusted_income_statement_total' => 'decimal:2',
            'difference' => 'decimal:2',
            'is_balanced' => 'boolean',
            'tolerance' => 'decimal:2',
            'category_totals' => 'array',
            'messages' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
