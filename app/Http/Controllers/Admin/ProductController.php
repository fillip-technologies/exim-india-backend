<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        return Product::query()
            ->with('category:id,name,slug')
            ->when($request->query('category_id'), function ($q, $id) {
                if (is_numeric($id)) {
                    return $q->where('category_id', (int) $id);
                }
                return $q->whereHas('category', fn ($c) => $c->where('slug', $id));
            })
            ->when($request->query('category'), fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->when($request->query('status'), function ($q, $status) {
                if ($status === 'active') return $q->where('is_active', true);
                if ($status === 'draft' || $status === 'inactive') return $q->where('is_active', false);
                return $q;
            })
            ->when($request->query('search'), fn ($q, $s) => $q->where(function ($sub) use ($s) {
                $sub->where('name', 'like', "%{$s}%")
                    ->orWhere('cas_no', 'like', "%{$s}%")
                    ->orWhere('model_number', 'like', "%{$s}%");
            }))
            ->orderBy('category_id')->orderBy('sort_order')->orderBy('id')
            ->paginate(max(1, min(100, (int) $request->query('limit', $request->query('per_page', 25)))));
    }

    public function store(ProductRequest $request)
    {
        $data = $request->validated();
        $specs = $data['analysis'] ?? [];
        $attributes = $data['attributes'] ?? [];
        unset($data['analysis'], $data['attributes']);

        $product = Product::create($data);
        $product->syncAnalysisSpecs($specs);
        $product->syncDetailAttributes($attributes);

        return response()->json($product->load('analysisSpecs', 'detailAttributes'), 201);
    }

    public function show(Product $product)
    {
        return $product->load('category:id,name,slug', 'analysisSpecs', 'detailAttributes', 'variants', 'productAttributes.attribute');
    }

    public function update(ProductRequest $request, Product $product)
    {
        $data = $request->validated();
        $hasSpecs = array_key_exists('analysis', $data);
        $hasAttributes = array_key_exists('attributes', $data);
        $specs = $data['analysis'] ?? [];
        $attributes = $data['attributes'] ?? [];
        unset($data['analysis'], $data['attributes']);

        $product->update($data);
        if ($hasSpecs) {
            $product->syncAnalysisSpecs($specs);
        }
        if ($hasAttributes) {
            $product->syncDetailAttributes($attributes);
        }

        return $product->load('analysisSpecs', 'detailAttributes');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->noContent();
    }
}
