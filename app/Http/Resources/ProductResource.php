<?php

namespace App\Http\Resources;

use App\Models\ProductPoint;
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
            'slug' => $this->slug,
            'name' => $this->name,
            'shortName' => $this->short_name,
            'tagline' => $this->tagline,
            'usageGuide' => $this->usage_guide,
            'variantMode' => $this->variant_mode ?? 'none',
            'defaultVariantId' => $this->default_variant_id,
            'isFeatured' => (bool) $this->is_featured,
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
            'extra' => empty($this->extra) ? null : $this->extra,
            'analysis' => $this->analysisRows(),
            'attributes' => $this->detailAttributeRows(),
            'applications' => $this->applicationsRows(),
            'techniques' => $this->techniquesRows(),
            'options' => $this->optionsRows(),
            'variants' => $this->variantsRows(),
            'fields' => $this->fieldsRows(),
            'bundles' => $this->bundlesRows(),
        ];
    }

    /** The product's own specs, falling back to its category's shared specs. */
    private function analysisRows(): array
    {
        $specs = $this->relationLoaded('analysisSpecs') ? $this->analysisSpecs : collect();

        if ($specs->isEmpty() && $this->relationLoaded('category') && $this->category) {
            $specs = $this->category->relationLoaded('analysisSpecs')
                ? $this->category->analysisSpecs
                : collect();
        }

        return $specs->map(fn ($s) => [
            'characteristic' => $s->characteristic,
            'requirement' => $s->requirement,
        ])->values()->all();
    }

    private function detailAttributeRows(): array
    {
        if (! $this->relationLoaded('detailAttributes')) {
            return [];
        }

        return $this->detailAttributes->map(fn ($a) => [
            'label' => $a->label,
            'value' => $a->value,
        ])->values()->all();
    }

    private function applicationsRows(): array
    {
        if (! $this->relationLoaded('points')) {
            return [];
        }

        return $this->points
            ->where('kind', ProductPoint::KIND_APPLICATION)
            ->pluck('title')
            ->values()
            ->all();
    }

    private function techniquesRows(): array
    {
        if (! $this->relationLoaded('points')) {
            return [];
        }

        return $this->points
            ->where('kind', ProductPoint::KIND_TECHNIQUE)
            ->map(fn ($p) => [
                'title' => $p->title,
                'desc' => $p->body,
                'icon' => $p->icon,
            ])
            ->values()
            ->all();
    }

    private function optionsRows(): array
    {
        if (! $this->relationLoaded('productAttributes') && ! $this->relationLoaded('attributes')) {
            return [];
        }

        $prodAttrs = $this->productAttributes;
        if ($prodAttrs->isEmpty()) {
            return [];
        }

        $pavs = $this->relationLoaded('productAttributeValues')
            ? $this->productAttributeValues
            : collect();

        $options = [];
        foreach ($prodAttrs as $pa) {
            $attr = $pa->attribute;
            if (! $attr) {
                continue;
            }

            $attrPavs = $pavs->filter(fn ($p) => $p->attributeValue && $p->attributeValue->attribute_id === $attr->id);

            $values = [];
            foreach ($attrPavs as $pav) {
                $val = $pav->attributeValue;
                $row = [
                    'id' => $pav->attribute_value_id,
                    'slug' => $val->slug,
                    'label' => $pav->resolved_label,
                    'code' => $val->code,
                ];

                if ($attr->input_type === 'swatch' || $attr->code === 'colour') {
                    $row['hex'] = $pav->resolved_hex;
                    $row['tone'] = $pav->resolved_tone;
                    $row['gradient'] = $val->computedGradient();
                    $row['isBlend'] = (bool) $val->is_blend;
                }

                if ($attr->input_type === 'pack' || $attr->code === 'pack_size') {
                    $row['container'] = $pav->container;
                    $row['target'] = $pav->target;
                    $row['numericValue'] = $val->numeric_value !== null ? (float) $val->numeric_value : null;
                    $row['unit'] = $val->unit;
                }

                if ($pav->image || $val->image) {
                    $row['image'] = $pav->image ?: $val->image;
                }

                $values[] = $row;
            }

            $options[] = [
                'id' => $attr->id,
                'code' => $attr->code,
                'name' => $attr->name,
                'type' => $attr->input_type,
                'unit' => $attr->unit,
                'isRequired' => (bool) $pa->is_required,
                'isVariantAxis' => (bool) $pa->is_variant_axis,
                'values' => $values,
            ];
        }

        return $options;
    }

    private function variantsRows(): array
    {
        if (! $this->relationLoaded('variants')) {
            return [];
        }

        return $this->variants->where('is_active', true)->map(function ($v) {
            $optionsMap = [];
            if ($v->relationLoaded('variantOptions')) {
                foreach ($v->variantOptions as $vo) {
                    $code = $vo->attribute?->code ?: 'attr_' . $vo->attribute_id;
                    $optionsMap[$code] = $vo->attribute_value_id;
                }
            }

            return [
                'id' => $v->id,
                'sku' => $v->sku,
                'options' => $optionsMap,
                'image' => $v->image,
                'imageUrl' => $v->image_url,
                'moq' => $v->moq ?: $this->moq,
                'supplyAbility' => $v->supply_ability ?: $this->supply_ability,
                'price' => $v->price !== null ? (float) $v->price : null,
                'currency' => $v->currency,
                'priceUnit' => $v->price_unit,
                'stockStatus' => $v->stock_status,
                'isDefault' => (bool) $v->is_default,
                'active' => (bool) $v->is_active,
            ];
        })->values()->all();
    }

    private function fieldsRows(): array
    {
        if (! $this->relationLoaded('fieldValues')) {
            return [];
        }

        $grouped = [];
        foreach ($this->fieldValues as $fv) {
            $def = $fv->fieldDefinition;
            if (! $def || ! $def->is_public) {
                continue;
            }

            $group = $def->group_name ?: 'General';
            if (! isset($grouped[$group])) {
                $grouped[$group] = [
                    'group' => $group,
                    'items' => [],
                ];
            }

            $grouped[$group]['items'][] = [
                'code' => $def->code,
                'label' => $def->label,
                'type' => $def->type,
                'value' => $fv->getTypedValue(),
                'unit' => $def->unit,
            ];
        }

        return array_values($grouped);
    }

    private function bundlesRows(): array
    {
        if (! $this->relationLoaded('bundles')) {
            return [];
        }

        return $this->bundles->where('is_active', true)->map(fn ($b) => [
            'id' => $b->id,
            'slug' => $b->slug,
            'name' => $b->name,
            'description' => $b->description,
            'image' => $b->image,
            'kind' => $b->kind,
            'pickCount' => $b->pick_count,
            'price' => $b->price !== null ? (float) $b->price : null,
            'currency' => $b->currency,
            'items' => $b->relationLoaded('items') ? $b->items->map(fn ($bi) => [
                'variantId' => $bi->variant_id,
                'quantity' => $bi->quantity,
            ])->values()->all() : [],
        ])->values()->all();
    }
}
