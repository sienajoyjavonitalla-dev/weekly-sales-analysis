<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_batch_id',
        'uploaded_file_id',
        'source_type',
        'source_sheet',
        'source_row_number',
        'customer_id',
        'order_number',
        'transaction_type',
        'transaction_date',
        'item_id',
        'description',
        'sold_to_id',
        'quantity_ordered',
        'amount',
        'location_id',
        'raw_values',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'quantity_ordered' => 'decimal:4',
            'amount' => 'decimal:2',
            'raw_values' => 'array',
            'source_row_number' => 'integer',
        ];
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function uploadedFile(): BelongsTo
    {
        return $this->belongsTo(UploadedFile::class);
    }
}
