<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesRowStatePlacement extends Model
{
    protected $fillable = [
        'sales_row_id',
        'product_category_id',
        'mapping_rule_id',
    ];

    public function salesRow(): BelongsTo
    {
        return $this->belongsTo(SalesRow::class);
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function mappingRule(): BelongsTo
    {
        return $this->belongsTo(MappingRule::class);
    }
}
