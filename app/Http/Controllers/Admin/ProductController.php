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
            ->when($request->query('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->query('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('category_id')->orderBy('sort_order')->orderBy('id')
            ->paginate(25);
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
        return $product->load('category:id,name,slug', 'analysisSpecs', 'detailAttributes');
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
