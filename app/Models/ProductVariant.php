<?php

namespace App\Models;

use App\Models\Concerns\HasAnalysisSpecs;
use App\Models\Concerns\HasDetailAttributes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as EloquentAttribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasAnalysisSpecs, HasDetailAttributes, SoftDeletes;

    public const STOCK_IN_STOCK     = 'in_stock';
    public const STOCK_LOW_STOCK    = 'low_stock';
    public const STOCK_OUT_OF_STOCK = 'out_of_stock';
    public const STOCK_ON_REQUEST   = 'on_request';

    protected $fillable = [
        'product_id',
        'sku',
        'signature',
        'image',
        'moq',
        'supply_ability',
        'price',
        'currency',
        'price_unit',
        'stock_status',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'price'      => 'decimal:2',
            'is_default' => 'boolean',
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ProductVariant $variant) {
            if (empty($variant->signature) && $variant->relationLoaded('variantOptions')) {
                $options = $variant->variantOptions->pluck('attribute_value_id', 'attribute_id')->all();
                if (! empty($options)) {
                    $variant->signature = static::buildSignature($options);
                }
            }
        });

        static::saved(function (ProductVariant $variant) {
            if ($variant->is_default && $variant->product_id) {
                static::where('product_id', $variant->product_id)
                    ->where('id', '!=', $variant->id)
                    ->update(['is_default' => false]);

                Product::where('id', $variant->product_id)->update(['default_variant_id' => $variant->id]);
            }
        });
    }

    protected function imageUrl(): EloquentAttribute
    {
        return EloquentAttribute::get(function () {
            if (! $this->image) {
                return $this->product?->image_url;
            }

            return str_starts_with($this->image, 'http')
                ? $this->image
                : asset('storage/' . ltrim($this->image, '/'));
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variantOptions(): HasMany
    {
        return $this->hasMany(VariantOption::class, 'variant_id');
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeValue::class,
            'variant_options',
            'variant_id',
            'attribute_value_id'
        )->withPivot('attribute_id');
    }

    public function contactItems(): HasMany
    {
        return $this->hasMany(ContactItem::class, 'variant_id');
    }

    public function fieldValues(): MorphMany
    {
        return $this->morphMany(FieldValue::class, 'owner');
    }

    public static function buildSignature(array $options): string
    {
        ksort($options, SORT_NUMERIC);
        $parts = [];
        foreach ($options as $attrId => $valId) {
            $parts[] = "{$attrId}:{$valId}";
        }
        return implode('|', $parts);
    }

    public function colorHex(): ?string
    {
        $colorOption = $this->attributeValues->first(fn ($v) => $v->attribute?->code === 'colour' || ! empty($v->hex));
        return $colorOption?->hex;
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
