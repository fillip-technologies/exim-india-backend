<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Every product has exactly the same keys, whatever its category.
 * `id` is the slug (as in the frontend URLs);
 * `productId` is the database id used when submitting an order.
 *
 * @mixin \App\Models\Product
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->slug,
            'productId' => $this->id,
            'name' => $this->name,
            'category' => $this->whenLoaded('category', fn () => [
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'groupKey' => $this->group_key,
            'groupName' => $this->group_name,
            'colorHex' => $this->color_hex,
            'image' => $this->image_url,
            'description' => $this->description,
            'moq' => $this->moq,
            'supplyAbility' => $this->supply_ability,
            'port' => $this->port,
            'casNo' => $this->cas_no,
            'otherNames' => $this->other_names,
            'mf' => $this->mf,
            'einecsNo' => $this->einecs_no,
            'femaNo' => $this->fema_no,
            'placeOfOrigin' => $this->place_of_origin,
            'types' => $this->types,
            'brandName' => $this->brand_name,
            'modelNumber' => $this->model_number,
            'grade' => $this->grade,
            'colorDesc' => $this->color_desc,
            'applicationSummary' => $this->application_summary,
            'purity' => $this->purity,
            'shelfLife' => $this->shelf_life,
            'packagingDetails' => $this->packaging_details,
            'deliveryDetail' => $this->delivery_detail,
            'storage' => $this->storage,
            'analysis' => $this->when($this->relationLoaded('analysisSpecs'), fn () => $this->analysisRows()),
            // Category-specific extras as uniform label/value pairs (C.I. No., E Number, Packaging, ...).
            'attributes' => $this->when($this->relationLoaded('detailAttributes'), fn () => $this->detailAttributes
                ->map(fn ($a) => ['label' => $a->label, 'value' => $a->value])->values()->all()),
        ];
    }

    /** The product's own specs, falling back to its category's shared specs. */
    private function analysisRows(): array
    {
        $specs = $this->analysisSpecs;

        if ($specs->isEmpty() && $this->relationLoaded('category')) {
            $specs = $this->category->analysisSpecs;
        }

        return $specs->map(fn ($s) => [
            'characteristic' => $s->characteristic,
            'requirement' => $s->requirement,
        ])->values()->all();
    }
}
