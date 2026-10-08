<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\Concerns\HasAnalysisSpecs;
use App\Models\Concerns\HasDetailAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasAnalysisSpecs, HasDetailAttributes, SoftDeletes;

    protected $fillable = [
        'category_id',
        'slug',
        'name',
        'group_key',
        'group_name',
        'color_hex',
        'image',
        'description',
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
        'extra',
        'sort_order',
        'is_active',
    ];

    protected $appends = ['image_url'];

    protected function imageUrl(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->image) {
                return null;
            }

            return str_starts_with($this->image, 'http')
                ? $this->image
                : asset('storage/' . ltrim($this->image, '/'));
        });
    }

    protected function casts(): array
    {
        return [
            'extra' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
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
