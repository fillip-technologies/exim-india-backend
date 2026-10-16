<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\BundleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BundleController extends Controller
{
    public function index()
    {
        return Bundle::ordered()
            ->with(['product:id,name,slug', 'items.variant'])
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', 'unique:bundles,slug'],
            'description' => ['nullable', 'string', 'max:5000'],
            'image'       => ['nullable', 'string', 'max:255'],
            'product_id'  => ['nullable', 'integer', 'exists:products,id'],
            'kind'        => ['required', Rule::in(['fixed', 'pick_n'])],
            'pick_count'  => ['nullable', 'integer'],
            'price'       => ['nullable', 'numeric'],
            'currency'    => ['nullable', 'string', 'size:3'],
            'is_active'   => ['nullable', 'boolean'],
            'sort_order'  => ['nullable', 'integer'],
            'items'       => ['nullable', 'array'],
            'items.*.variant_id' => ['required_with:items', 'integer', 'exists:product_variants,id'],
            'items.*.quantity'   => ['nullable', 'integer', 'min:1'],
        ]);

        $items = $validated['items'] ?? [];
        unset($validated['items']);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $bundle = DB::transaction(function () use ($validated, $items) {
            $bundle = Bundle::create($validated);
            foreach ($items as $item) {
                BundleItem::create([
                    'bundle_id'  => $bundle->id,
                    'variant_id' => $item['variant_id'],
                    'quantity'   => $item['quantity'] ?? 1,
                ]);
            }

            return $bundle;
        });

        return response()->json($bundle->load(['product:id,name,slug', 'items.variant']), 201);
    }

    public function show(Bundle $bundle)
    {
        return $bundle->load(['product:id,name,slug', 'items.variant.attributeValues']);
    }

    public function update(Request $request, Bundle $bundle)
    {
        $validated = $request->validate([
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', Rule::unique('bundles', 'slug')->ignore($bundle->id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'image'       => ['nullable', 'string', 'max:255'],
            'product_id'  => ['nullable', 'integer', 'exists:products,id'],
            'kind'        => ['sometimes', 'required', Rule::in(['fixed', 'pick_n'])],
            'pick_count'  => ['nullable', 'integer'],
            'price'       => ['nullable', 'numeric'],
            'currency'    => ['nullable', 'string', 'size:3'],
            'is_active'   => ['nullable', 'boolean'],
            'sort_order'  => ['nullable', 'integer'],
            'items'       => ['nullable', 'array'],
            'items.*.variant_id' => ['required_with:items', 'integer', 'exists:product_variants,id'],
            'items.*.quantity'   => ['nullable', 'integer', 'min:1'],
        ]);

        $items = $validated['items'] ?? null;
        unset($validated['items']);

        DB::transaction(function () use ($bundle, $validated, $items) {
            $bundle->update($validated);
            if ($items !== null) {
                $bundle->items()->delete();
                foreach ($items as $item) {
                    BundleItem::create([
                        'bundle_id'  => $bundle->id,
                        'variant_id' => $item['variant_id'],
                        'quantity'   => $item['quantity'] ?? 1,
                    ]);
                }
            }
        });

        return response()->json($bundle->load(['product:id,name,slug', 'items.variant']));
    }

    public function destroy(Bundle $bundle)
    {
        $bundle->delete();

        return response()->noContent();
    }
}
