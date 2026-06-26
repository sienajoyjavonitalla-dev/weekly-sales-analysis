<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UploadedFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_batch_id',
        'file_type',
        'original_name',
        'storage_path',
        'mime_type',
        'size_bytes',
        'sha256_checksum',
        'sheet_names',
        'status',
        'validation_errors',
    ];

    protected function casts(): array
    {
        return [
            'sheet_names' => 'array',
            'validation_errors' => 'array',
            'size_bytes' => 'integer',
        ];
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
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
}
