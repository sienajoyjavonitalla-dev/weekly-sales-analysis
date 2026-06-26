<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomeStatementLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_batch_id',
        'uploaded_file_id',
        'source_sheet',
        'source_row_number',
        'account_number',
        'description',
        'current_period_amount',
        'current_period_percent',
        'year_to_date_amount',
        'year_to_date_percent',
        'raw_values',
    ];

    protected function casts(): array
    {
        return [
            'current_period_amount' => 'decimal:2',
            'current_period_percent' => 'decimal:4',
            'year_to_date_amount' => 'decimal:2',
            'year_to_date_percent' => 'decimal:4',
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
