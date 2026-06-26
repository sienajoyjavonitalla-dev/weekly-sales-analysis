<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'report_family',
        'sales_analysis_bucket',
        'total_sales_row_label',
        'weekly_meter_row_label',
        'sort_order',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function mappingRules(): HasMany
    {
        return $this->hasMany(MappingRule::class);
    }

    public function salesRows(): HasMany
    {
        return $this->hasMany(SalesRow::class);
    }
}
