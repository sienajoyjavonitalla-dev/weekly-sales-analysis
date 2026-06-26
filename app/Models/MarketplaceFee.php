<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_batch_id',
        'marketplace',
        'fee_date',
        'description',
        'amount',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'fee_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
