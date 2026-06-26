<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MappingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_category_id',
        'name',
        'source_type',
        'match_field',
        'match_operator',
        'pattern',
        'target_bucket',
        'priority',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
            'priority' => 'integer',
        ];
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }
}
