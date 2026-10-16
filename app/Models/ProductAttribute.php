<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttribute extends Pivot
{
    protected $table = 'product_attributes';

    protected $fillable = [
        'product_id',
        'attribute_id',
        'sort_order',
        'is_required',
        'is_variant_axis',
    ];

    protected function casts(): array
    {
        return [
            'is_required'     => 'boolean',
            'is_variant_axis' => 'boolean',
            'sort_order'      => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
