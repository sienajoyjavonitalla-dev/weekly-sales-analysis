<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_batch_id',
        'report_type',
        'status',
        'file_name',
        'storage_path',
        'sha256_checksum',
        'summary',
        'errors',
        'generated_by_user_id',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'summary' => 'array',
            'errors' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }
}
