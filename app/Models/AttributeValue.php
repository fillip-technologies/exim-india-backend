<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributeValue extends Model
{
    protected $fillable = [
        'attribute_id',
        'slug',
        'label',
        'code',
        'hex',
        'gradient',
        'image',
        'tone',
        'numeric_value',
        'unit',
        'group_name',
        'is_blend',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_blend'      => 'boolean',
            'is_active'     => 'boolean',
            'numeric_value' => 'decimal:3',
            'sort_order'    => 'integer',
        ];
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(AttributeValueComponent::class, 'blend_value_id')->orderBy('sort_order');
    }

    public function componentValues(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeValue::class,
            'attribute_value_components',
            'blend_value_id',
            'component_value_id'
        )->withPivot('ratio', 'sort_order')->orderByPivot('sort_order');
    }

    public function productAttributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_attribute_values')
            ->withPivot('label_override', 'hex_override', 'tone_override', 'image', 'container', 'target', 'extra', 'sort_order')
            ->withTimestamps();
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'variant_options', 'attribute_value_id', 'variant_id');
    }

    public function computedGradient(): ?string
    {
        if (! $this->is_blend) {
            return $this->gradient;
        }

        $components = $this->componentValues;
        if ($components->isEmpty()) {
            return $this->gradient;
        }

        $stops = [];
        $total = $components->count();
        foreach ($components as $index => $comp) {
            $hex = $comp->hex ?: '#ffffff';
            $pct = $total > 1 ? round(($index / ($total - 1)) * 100) : 0;
            $stops[] = "{$hex} {$pct}%";
        }

        return 'radial-gradient(circle at 35% 35%, ' . implode(', ', $stops) . ')';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
