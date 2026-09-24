<?php

namespace App\Models\Concerns;

use App\Models\DetailAttribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasDetailAttributes
{
    public function detailAttributes(): MorphMany
    {
        return $this->morphMany(DetailAttribute::class, 'attributable')->orderBy('sort_order')->orderBy('id');
    }

    /** Replace all rows with the given [{label, value}] list. */
    public function syncDetailAttributes(array $attributes): void
    {
        $this->detailAttributes()->delete();

        $rows = [];
        foreach (array_values($attributes) as $i => $attr) {
            $rows[] = ['label' => $attr['label'], 'value' => $attr['value'], 'sort_order' => $i + 1];
        }

        $this->detailAttributes()->createMany($rows);
    }
}
