<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FieldDefinition extends Model
{
    public const TYPE_TEXT        = 'text';
    public const TYPE_TEXTAREA    = 'textarea';
    public const TYPE_NUMBER      = 'number';
    public const TYPE_SELECT      = 'select';
    public const TYPE_MULTISELECT = 'multiselect';
    public const TYPE_BOOLEAN     = 'boolean';
    public const TYPE_URL         = 'url';
    public const TYPE_FILE        = 'file';
    public const TYPE_COLOR       = 'color';

    public const APPLIES_PRODUCT = 'product';
    public const APPLIES_VARIANT = 'variant';

    protected $fillable = [
        'code',
        'label',
        'type',
        'options',
        'unit',
        'group_name',
        'applies_to',
        'is_filterable',
        'is_public',
        'is_searchable',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options'       => 'array',
            'is_filterable' => 'boolean',
            'is_public'     => 'boolean',
            'is_searchable' => 'boolean',
            'sort_order'    => 'integer',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(FieldAssignment::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(FieldValue::class);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function scopeFilterable(Builder $query): Builder
    {
        return $query->where('is_filterable', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
