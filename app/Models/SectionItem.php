<?php

namespace App\Models;

use App\Models\Concerns\HasDetailAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionItem extends Model
{
    use HasDetailAttributes;

    protected $fillable = ['category_section_id', 'title', 'subtitle', 'color_hex', 'sort_order'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(CategorySection::class, 'category_section_id');
    }
}
