<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Category */
class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'path' => '/products/'.$this->slug,
            'description' => $this->description,
            'image' => $this->image_url,
            'productCount' => $this->whenCounted('products'),
            'analysis' => $this->whenLoaded('analysisSpecs', fn () => $this->specRows($this->analysisSpecs)),
            'sections' => $this->whenLoaded('sections', fn () => $this->sections->map(fn ($section) => [
                'key' => $section->key,
                'title' => $section->title,
                'items' => $section->items->map(fn ($item) => [
                    'title' => $item->title,
                    'subtitle' => $item->subtitle,
                    'colorHex' => $item->color_hex,
                    'attributes' => $item->detailAttributes
                        ->map(fn ($a) => ['label' => $a->label, 'value' => $a->value])->values()->all(),
                ])->values()->all(),
            ])->values()->all()),
        ];
    }

    private function specRows($specs): array
    {
        return $specs->map(fn ($s) => [
            'characteristic' => $s->characteristic,
            'requirement' => $s->requirement,
        ])->all();
    }
}
