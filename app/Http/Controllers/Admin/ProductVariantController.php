<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductVariantController extends Controller
{
    public function index(Product $product)
    {
        return $product->variants()
            ->ordered()
            ->with(['variantOptions.attribute', 'variantOptions.attributeValue'])
            ->get();
    }

    public function generate(Request $request, Product $product)
    {
        $axes = $product->productAttributes()
            ->where('is_variant_axis', true)
            ->orderBy('sort_order')
            ->with('attribute')
            ->get();

        if ($axes->isEmpty()) {
            return response()->json([
                'message' => 'Product has no variant axes configured. Please configure product options first.',
                'created' => 0,
            ], 422);
        }

        // Collect enabled values per axis
        $pavs = $product->productAttributeValues()->with('attributeValue')->get();
        $axesValues = [];

        foreach ($axes as $axis) {
            $attrId = $axis->attribute_id;
            $vals = $pavs->filter(fn ($p) => $p->attributeValue && $p->attributeValue->attribute_id === $attrId);
            if ($vals->isEmpty()) {
                return response()->json([
                    'message' => "Axis '{$axis->attribute->name}' has no enabled values.",
                    'created' => 0,
                ], 422);
            }
            $axesValues[$attrId] = $vals->pluck('attribute_value_id')->all();
        }

        // Cartesian product
        $combinations = $this->cartesian($axesValues);
        $createdCount = 0;
        $hasDefault = $product->variants()->where('is_default', true)->exists();

        DB::transaction(function () use ($product, $combinations, &$createdCount, $hasDefault) {
            $existingSignatures = $product->variants()->pluck('signature')->all();
            $existingSet = array_flip($existingSignatures);

            foreach ($combinations as $combo) {
                // $combo is [attr_id => val_id, ...]
                $signature = ProductVariant::buildSignature($combo);
                if (isset($existingSet[$signature])) {
                    continue;
                }

                $skuParts = array_values($combo);
                $sku = "EX-{$product->id}-" . implode('-', $skuParts);

                $isDefault = (! $hasDefault && $createdCount === 0);
                $variant = ProductVariant::create([
                    'product_id'   => $product->id,
                    'signature'    => $signature,
                    'sku'          => $sku,
                    'moq'          => $product->moq,
                    'stock_status' => ProductVariant::STOCK_ON_REQUEST,
                    'is_default'   => $isDefault,
                    'is_active'    => true,
                    'sort_order'   => $createdCount + 1,
                ]);

                foreach ($combo as $attrId => $valId) {
                    VariantOption::create([
                        'variant_id'         => $variant->id,
                        'attribute_id'       => $attrId,
                        'attribute_value_id' => $valId,
                    ]);
                }

                if ($isDefault) {
                    $product->updateQuietly(['default_variant_id' => $variant->id]);
                    $hasDefault = true;
                }

                $createdCount++;
            }
        });

        return response()->json([
            'message' => "Generated {$createdCount} new variants.",
            'created' => $createdCount,
            'total'   => $product->variants()->count(),
        ]);
    }

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'sku'            => ['nullable', 'string', 'max:100', 'unique:product_variants,sku'],
            'moq'            => ['nullable', 'string', 'max:100'],
            'supply_ability' => ['nullable', 'string', 'max:255'],
            'price'          => ['nullable', 'numeric'],
            'currency'       => ['nullable', 'string', 'size:3'],
            'price_unit'     => ['nullable', 'string', 'max:50'],
            'stock_status'   => ['nullable', Rule::in(['in_stock', 'low_stock', 'out_of_stock', 'on_request'])],
            'is_default'     => ['nullable', 'boolean'],
            'is_active'      => ['nullable', 'boolean'],
            'options'        => ['required', 'array', 'min:1'],
            'options.*.attribute_id'       => ['required', 'integer', 'exists:attributes,id'],
            'options.*.attribute_value_id' => ['required', 'integer', 'exists:attribute_values,id'],
        ]);

        $optionsMap = [];
        foreach ($validated['options'] as $opt) {
            $optionsMap[$opt['attribute_id']] = $opt['attribute_value_id'];
        }

        $signature = ProductVariant::buildSignature($optionsMap);
        if ($product->variants()->where('signature', $signature)->exists()) {
            return response()->json(['message' => 'A variant with this combination already exists.'], 422);
        }

        $sku = $validated['sku'] ?? ("EX-{$product->id}-" . implode('-', array_values($optionsMap)));
        unset($validated['options']);

        $variant = DB::transaction(function () use ($product, $validated, $signature, $sku, $optionsMap) {
            $variant = ProductVariant::create([
                ...$validated,
                'product_id' => $product->id,
                'signature'  => $signature,
                'sku'        => $sku,
            ]);

            foreach ($optionsMap as $attrId => $valId) {
                VariantOption::create([
                    'variant_id'         => $variant->id,
                    'attribute_id'       => $attrId,
                    'attribute_value_id' => $valId,
                ]);
            }

            return $variant;
        });

        return response()->json($variant->load(['variantOptions.attribute', 'variantOptions.attributeValue']), 201);
    }

    public function update(Request $request, ProductVariant $variant)
    {
        $validated = $request->validate([
            'sku'            => ['sometimes', 'required', 'string', 'max:100', Rule::unique('product_variants', 'sku')->ignore($variant->id)],
            'moq'            => ['nullable', 'string', 'max:100'],
            'supply_ability' => ['nullable', 'string', 'max:255'],
            'price'          => ['nullable', 'numeric'],
            'currency'       => ['nullable', 'string', 'size:3'],
            'price_unit'     => ['nullable', 'string', 'max:50'],
            'stock_status'   => ['nullable', Rule::in(['in_stock', 'low_stock', 'out_of_stock', 'on_request'])],
            'is_default'     => ['nullable', 'boolean'],
            'is_active'      => ['nullable', 'boolean'],
            'image'          => ['nullable', 'string', 'max:255'],
            'sort_order'     => ['nullable', 'integer'],
        ]);

        $variant->update($validated);

        return response()->json($variant->load(['variantOptions.attribute', 'variantOptions.attributeValue']));
    }

    public function destroy(ProductVariant $variant)
    {
        $variant->delete();

        return response()->noContent();
    }

    /** Helper: Cartesian product of an associative array of arrays [key => [val, ...]] */
    protected function cartesian(array $input): array
    {
        $result = [[]];
        foreach ($input as $key => $values) {
            $append = [];
            foreach ($result as $product) {
                foreach ($values as $item) {
                    $append[] = $product + [$key => $item];
                }
            }
            $result = $append;
        }

        return $result;
    }
}
