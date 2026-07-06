<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_batch_id',
        'uploaded_file_id',
        'product_category_id',
        'mapping_rule_id',
        'source_sheet',
        'source_row_number',
        'source_bucket',
        'item_id',
        'description',
        'customer_id',
        'customer_name',
        'invoice_number',
        'sales_rep_id',
        'country',
        'bill_to_state',
        'invoice_date',
        'quantity_ordered',
        'amount',
        'classification_status',
        'classified_by_user_id',
        'classified_at',
        'classification_notes',
        'raw_values',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'quantity_ordered' => 'decimal:4',
            'amount' => 'decimal:2',
            'raw_values' => 'array',
            'source_row_number' => 'integer',
            'classified_at' => 'datetime',
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

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function mappingRule(): BelongsTo
    {
        return $this->belongsTo(MappingRule::class);
    }

    public function statePlacement(): HasOne
    {
        return $this->hasOne(SalesRowStatePlacement::class);
    }

    public function classifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'classified_by_user_id');
    }
}
