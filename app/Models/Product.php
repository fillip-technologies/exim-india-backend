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

class Product extends Model
{
    use HasAnalysisSpecs, HasDetailAttributes, SoftDeletes;

    public const VARIANT_MODE_NONE        = 'none';
    public const VARIANT_MODE_SINGLE_AXIS = 'single_axis';
    public const VARIANT_MODE_MULTI_AXIS  = 'multi_axis';

    protected $fillable = [
        'category_id',
        'slug',
        'name',
        'short_name',
        'tagline',
        'group_key',
        'group_name',
        'image',
        'description',
        'usage_guide',
        'moq',
        'supply_ability',
        'port',
        'cas_no',
        'other_names',
        'mf',
        'einecs_no',
        'fema_no',
        'place_of_origin',
        'types',
        'brand_name',
        'model_number',
        'grade',
        'color_desc',
        'application_summary',
        'purity',
        'shelf_life',
        'packaging_details',
        'delivery_detail',
        'storage',
        'variant_mode',
        'default_variant_id',
        'extra',
        'sort_order',
        'is_active',
        'is_featured',
    ];

    protected $appends = ['image_url'];

    protected function imageUrl(): EloquentAttribute
    {
        return EloquentAttribute::get(function () {
            if (! $this->image) {
                return null;
            }

            return str_starts_with($this->image, 'http')
                ? $this->image
                : asset('storage/' . ltrim($this->image, '/'));
        });
    }

    protected function colorHex(): EloquentAttribute
    {
        return EloquentAttribute::get(fn () => $this->primaryColorHex());
    }

    protected function casts(): array
    {
        return [
            'extra'       => 'array',
            'is_active'   => 'boolean',
            'is_featured' => 'boolean',
            'sort_order'  => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function defaultVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'default_variant_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function activeVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'product_attributes')
            ->withPivot('sort_order', 'is_required', 'is_variant_axis')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class)->orderBy('sort_order');
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'product_attribute_values')
            ->withPivot('label_override', 'hex_override', 'tone_override', 'image', 'container', 'target', 'extra', 'sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function productAttributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class)->orderBy('sort_order');
    }

    public function points(): HasMany
    {
        return $this->hasMany(ProductPoint::class)->orderBy('sort_order')->orderBy('id');
    }

    public function bundles(): HasMany
    {
        return $this->hasMany(Bundle::class)->orderBy('sort_order')->orderBy('id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function fieldAssignments(): HasMany
    {
        return $this->hasMany(FieldAssignment::class)->orderBy('sort_order');
    }

    public function fieldValues(): MorphMany
    {
        return $this->morphMany(FieldValue::class, 'owner');
    }

    public function primaryColorHex(): ?string
    {
        if ($this->default_variant_id) {
            $variant = $this->relationLoaded('defaultVariant')
                ? $this->defaultVariant
                : $this->defaultVariant()->with('attributeValues.attribute')->first();
            $hex = $variant?->colorHex();
            if ($hex) {
                return $hex;
            }
        }

        if ($this->relationLoaded('productAttributeValues')) {
            $pav = $this->productAttributeValues->first(fn ($p) => ! empty($p->resolved_hex));
            if ($pav) {
                return $pav->resolved_hex;
            }
        }

        if ($this->relationLoaded('attributeValues')) {
            $val = $this->attributeValues->first(fn ($v) => ! empty($v->hex) || ! empty($v->pivot?->hex_override));
            if ($val) {
                return $val->pivot?->hex_override ?: $val->hex;
            }
        }

        // Already eager-loaded and nothing matched: don't hit the database again.
        if ($this->relationLoaded('productAttributeValues')) {
            return null;
        }

        return $this->attributeValues()->whereNotNull('hex')->first()?->hex;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
