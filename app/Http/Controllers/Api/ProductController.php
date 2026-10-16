<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\CatalogService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductController extends Controller
{
    protected array $eagerLoads = [
        'category',
        'analysisSpecs',
        'detailAttributes',
        'productAttributes.attribute',
        'productAttributeValues.attributeValue.attribute',
        'variants.variantOptions.attribute',
        'defaultVariant.attributeValues.attribute',
        'points',
        'fieldValues.fieldDefinition',
        'bundles.items.variant',
    ];

    public function __construct(private CatalogService $catalog) {}

    public function catalog(Request $request)
    {
        $products = $this->catalog->allProducts($request->all());
        $products->load([...$this->eagerLoads, 'category.analysisSpecs']);

        return ProductResource::collection($products);
    }

    public function index(Request $request, string $slug)
    {
        $category = $this->catalog->category($slug)->load('analysisSpecs');
        // `?per_page=` switches to a paginated response (data + links + meta);
        // without it the whole category is returned.
        $perPage = $request->filled('per_page') ? (int) $request->query('per_page') : null;
        $products = $this->catalog->products($category, $request->all(), $perPage);

        $items = $products instanceof LengthAwarePaginator ? $products->getCollection() : $products;
        $items->load($this->eagerLoads)->each->setRelation('category', $category);

        return ProductResource::collection($products);
    }

    public function show(string $slug, string $productSlug)
    {
        $category = $this->catalog->category($slug)->load('analysisSpecs');
        $product = $this->catalog->product($category, $productSlug)->load($this->eagerLoads);
        $product->setRelation('category', $category);

        return new ProductResource($product);
    }

    public function showDirect(string $productSlug)
    {
        $query = Product::query()->active()
            ->whereHas('category', fn ($q) => $q->where('is_active', true));

        // Numeric values are tried as a primary key first, then as a slug.
        $product = is_numeric($productSlug)
            ? (clone $query)->whereKey((int) $productSlug)->first()
            : null;
        $product ??= $query->where('slug', $productSlug)->firstOrFail();

        $product->load([...$this->eagerLoads, 'category.analysisSpecs']);

        return new ProductResource($product);
    }
}
