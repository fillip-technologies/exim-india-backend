<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $rows = json_decode(file_get_contents(__DIR__.'/data/products.json'), true);
        $categories = Category::pluck('id', 'slug');

        foreach ($rows as $row) {
            $categoryId = $categories[$row['category']] ?? null;
            $specs = $row['analysis'] ?? [];
            $attributes = $row['attributes'] ?? [];
            unset($row['category'], $row['analysis'], $row['attributes']);

            if (! $categoryId) {
                continue;
            }

            $product = Product::updateOrCreate(
                ['category_id' => $categoryId, 'slug' => $row['slug']],
                $row,
            );
            $product->syncAnalysisSpecs($specs);
            $product->syncDetailAttributes($attributes);
        }
    }
}
