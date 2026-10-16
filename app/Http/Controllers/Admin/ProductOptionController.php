<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductOptionController extends Controller
{
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'variant_mode'                       => ['nullable', Rule::in(['none', 'single_axis', 'multi_axis'])],
            'options'                            => ['required', 'array'],
            'options.*.attribute_id'             => ['required', 'integer', 'exists:attributes,id'],
            'options.*.sort_order'               => ['nullable', 'integer'],
            'options.*.is_required'              => ['nullable', 'boolean'],
            'options.*.is_variant_axis'          => ['nullable', 'boolean'],
            'options.*.values'                   => ['nullable', 'array'],
            'options.*.values.*.attribute_value_id' => ['required', 'integer', 'exists:attribute_values,id'],
            'options.*.values.*.label_override'  => ['nullable', 'string', 'max:255'],
            'options.*.values.*.hex_override'    => ['nullable', 'string', 'max:9'],
            'options.*.values.*.tone_override'   => ['nullable', 'string', 'max:255'],
            'options.*.values.*.image'           => ['nullable', 'string', 'max:255'],
            'options.*.values.*.container'       => ['nullable', 'string', 'max:100'],
            'options.*.values.*.target'          => ['nullable', 'string', 'max:255'],
            'options.*.values.*.sort_order'      => ['nullable', 'integer'],
        ]);

        DB::transaction(function () use ($product, $validated) {
            if (! empty($validated['variant_mode'])) {
                $product->update(['variant_mode' => $validated['variant_mode']]);
            }

            // Sync product attributes
            ProductAttribute::where('product_id', $product->id)->delete();
            $enabledValueIds = [];

            foreach ($validated['options'] as $idx => $opt) {
                ProductAttribute::create([
                    'product_id'      => $product->id,
                    'attribute_id'    => $opt['attribute_id'],
                    'sort_order'      => $opt['sort_order'] ?? ($idx + 1),
                    'is_required'     => $opt['is_required'] ?? true,
                    'is_variant_axis' => $opt['is_variant_axis'] ?? true,
                ]);

                foreach ($opt['values'] ?? [] as $vIdx => $val) {
                    $valId = $val['attribute_value_id'];
                    $enabledValueIds[] = $valId;

                    ProductAttributeValue::updateOrCreate([
                        'product_id'         => $product->id,
                        'attribute_value_id' => $valId,
                    ], [
                        'label_override' => $val['label_override'] ?? null,
                        'hex_override'   => $val['hex_override'] ?? null,
                        'tone_override'  => $val['tone_override'] ?? null,
                        'image'          => $val['image'] ?? null,
                        'container'      => $val['container'] ?? null,
                        'target'         => $val['target'] ?? null,
                        'sort_order'     => $val['sort_order'] ?? ($vIdx + 1),
                    ]);
                }
            }

            // Clean up detached product attribute values
            ProductAttributeValue::where('product_id', $product->id)
                ->whereNotIn('attribute_value_id', $enabledValueIds)
                ->delete();
        });

        return response()->json([
            'message' => 'Product options updated successfully.',
            'product' => $product->fresh(['productAttributes.attribute', 'productAttributeValues.attributeValue']),
        ]);
    }
}
