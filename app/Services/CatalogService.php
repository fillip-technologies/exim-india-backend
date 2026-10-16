<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogService
{
    public function categories(bool $navOnly = false): Collection
    {
        return Category::active()
            ->when($navOnly, fn ($q) => $q->inNav())
            ->ordered()
            ->withCount([
                'products' => fn ($q) => $q->active(),
            ])->get();
    }

    public function category(string $slug): Category
    {
        return Category::active()->where('slug', $slug)->firstOrFail();
    }

    public function products(Category $category, array $filters = []): Collection
    {
        $query = $category->products()->active()->ordered();
        $this->applyFilters($query, $filters);

        return $query->get();
    }

    public function allProducts(array $filters = []): Collection
    {
        $query = Product::active()
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->ordered();
        $this->applyFilters($query, $filters);

        return $query->get();
    }

    public function product(Category $category, string $slug): Product
    {
        return $category->products()->active()->where('slug', $slug)->firstOrFail();
    }

    protected function applyFilters(Builder|HasMany $query, array $filters): void
    {
        $groupKey = $filters['group_key'] ?? $filters['groupKey'] ?? null;
        if ($groupKey) {
            $query->where('group_key', $groupKey);
        }

        $categorySlug = $filters['category'] ?? null;
        if ($categorySlug) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
        }

        if (! empty($filters['featured']) || ! empty($filters['is_featured'])) {
            $query->featured();
        }

        $searchTerm = $filters['q'] ?? $filters['search'] ?? null;
        if ($searchTerm) {
            $query->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%")
                    ->orWhere('short_name', 'like', "%{$searchTerm}%")
                    ->orWhere('cas_no', 'like', "%{$searchTerm}%");
            });
        }

        // Dynamic attribute filters
        $knownAttributes = Attribute::pluck('code')->all();
        foreach ($knownAttributes as $attrCode) {
            if (! empty($filters[$attrCode])) {
                $valSlugOrId = $filters[$attrCode];
                $query->where(function ($subQ) use ($attrCode, $valSlugOrId) {
                    $subQ->whereHas('variants', function ($vq) use ($attrCode, $valSlugOrId) {
                        $vq->where('is_active', true)
                            ->whereHas('variantOptions', function ($voq) use ($attrCode, $valSlugOrId) {
                                $voq->whereHas('attribute', fn ($aq) => $aq->where('code', $attrCode))
                                    ->whereHas('attributeValue', function ($avq) use ($valSlugOrId) {
                                        if (is_numeric($valSlugOrId)) {
                                            $avq->where('id', $valSlugOrId);
                                        } else {
                                            $avq->where('slug', $valSlugOrId);
                                        }
                                    });
                            });
                    })->orWhereHas('productAttributeValues', function ($paq) use ($attrCode, $valSlugOrId) {
                        $paq->whereHas('attributeValue', function ($avq) use ($attrCode, $valSlugOrId) {
                            $avq->whereHas('attribute', fn ($aq) => $aq->where('code', $attrCode))
                                ->when(is_numeric($valSlugOrId), fn ($q) => $q->where('id', $valSlugOrId))
                                ->when(! is_numeric($valSlugOrId), fn ($q) => $q->where('slug', $valSlugOrId));
                        });
                    });
                });
            }
        }
    }
}
