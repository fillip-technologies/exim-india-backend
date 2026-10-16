<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttributeValue extends Model
{
    protected $table = 'product_attribute_values';

    protected $fillable = [
        'product_id',
        'attribute_value_id',
        'label_override',
        'hex_override',
        'tone_override',
        'image',
        'container',
        'target',
        'extra',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'extra'      => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class);
    }

    public function getResolvedLabelAttribute(): ?string
    {
        return $this->label_override ?: $this->attributeValue?->label;
    }

    public function getResolvedHexAttribute(): ?string
    {
        return $this->hex_override ?: $this->attributeValue?->hex;
    }

    public function getResolvedToneAttribute(): ?string
    {
        return $this->tone_override ?: $this->attributeValue?->tone;
    }
}
