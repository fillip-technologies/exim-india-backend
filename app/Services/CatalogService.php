<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class CatalogService
{
    public function categories(): Collection
    {
        return Category::active()->ordered()->withCount([
            'products' => fn ($q) => $q->active(),
        ])->get();
    }

    public function category(string $slug): Category
    {
        return Category::active()->where('slug', $slug)->firstOrFail();
    }

    public function products(Category $category, ?string $groupKey = null): Collection
    {
        return $category->products()
            ->active()
            ->ordered()
            ->when($groupKey, fn ($q) => $q->where('group_key', $groupKey))
            ->get();
    }

    public function product(Category $category, string $slug): Product
    {
        return $category->products()->active()->where('slug', $slug)->firstOrFail();
    }
}
