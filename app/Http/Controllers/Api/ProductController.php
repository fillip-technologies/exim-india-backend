<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Services\CatalogService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private CatalogService $catalog) {}

    public function index(Request $request, string $slug)
    {
        $category = $this->catalog->category($slug)->load('analysisSpecs');
        $products = $this->catalog->products($category, $request->query('group_key'));
        $products->load('analysisSpecs', 'detailAttributes')->each->setRelation('category', $category);

        return ProductResource::collection($products);
    }

    public function show(string $slug, string $productSlug)
    {
        $category = $this->catalog->category($slug)->load('analysisSpecs');
        $product = $this->catalog->product($category, $productSlug)->load('analysisSpecs', 'detailAttributes');
        $product->setRelation('category', $category);

        return new ProductResource($product);
    }
}
