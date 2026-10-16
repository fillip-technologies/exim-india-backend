<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeValueComponent extends Model
{
    protected $fillable = [
        'blend_value_id',
        'component_value_id',
        'ratio',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'ratio'      => 'float',
            'sort_order' => 'integer',
        ];
    }

    public function blendValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class, 'blend_value_id');
    }

    public function componentValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class, 'component_value_id');
    }
}
