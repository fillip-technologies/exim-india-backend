<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $rows = json_decode(file_get_contents(__DIR__.'/data/products.json'), true);
        $categories = Category::pluck('id', 'slug');
        $colourAttr = Attribute::firstOrCreate(
            ['code' => 'colour'],
            ['name' => 'Colour', 'input_type' => 'swatch', 'sort_order' => 1]
        );

        foreach ($rows as $row) {
            $categoryId = $categories[$row['category']] ?? null;
            $specs = $row['analysis'] ?? [];
            $attributes = $row['attributes'] ?? [];
            $colorHex = $row['color_hex'] ?? null;
            unset($row['category'], $row['analysis'], $row['attributes'], $row['color_hex']);

            if (! $categoryId) {
                continue;
            }

            $product = Product::updateOrCreate(
                ['category_id' => $categoryId, 'slug' => $row['slug']],
                $row,
            );
            $product->syncAnalysisSpecs($specs);
            $product->syncDetailAttributes($attributes);

            if ($colorHex) {
                $val = AttributeValue::firstOrCreate(
                    ['attribute_id' => $colourAttr->id, 'slug' => $product->slug . '-color'],
                    [
                        'label'      => $product->color_desc ?: $product->name,
                        'hex'        => $colorHex,
                        'sort_order' => 0,
                    ]
                );

                $product->attributeValues()->syncWithoutDetaching([$val->id]);

                $variant = $product->variants()->firstOrCreate(
                    ['signature' => "{$colourAttr->id}:{$val->id}"],
                    [
                        'sku'            => 'EX-' . $product->id . '-' . strtoupper(substr(str_replace('-', '', $product->slug), 0, 10)) . '-STD',
                        'moq'            => $product->moq,
                        'supply_ability' => $product->supply_ability,
                        'is_default'     => true,
                        'is_active'      => true,
                    ]
                );

                $variant->variantOptions()->firstOrCreate([
                    'attribute_id'       => $colourAttr->id,
                    'attribute_value_id' => $val->id,
                ]);

                $product->updateQuietly(['default_variant_id' => $variant->id]);
            }
        }
    }
}
