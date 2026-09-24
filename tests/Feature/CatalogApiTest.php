<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(array $attrs = []): Category
    {
        return Category::create([
            'name' => 'Blended Colours',
            'slug' => 'blended-colours',
            'sort_order' => 1,
            ...$attrs,
        ]);
    }

    private function makeProduct(Category $category, array $attrs = []): Product
    {
        return $category->products()->create([
            'name' => 'Egg Yellow',
            'slug' => 'egg-yellow',
            'color_hex' => '#eab308',
            'moq' => '250 Kgs',
            ...$attrs,
        ]);
    }

    public function test_lists_only_active_categories_in_order(): void
    {
        $this->makeCategory();
        $this->makeCategory(['name' => 'Lake', 'slug' => 'lake', 'sort_order' => 2]);
        $this->makeCategory(['name' => 'Hidden', 'slug' => 'hidden', 'is_active' => false]);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.slug', 'blended-colours')
            ->assertJsonPath('data.0.path', '/products/blended-colours');
    }

    public function test_product_detail_uses_camel_case_and_slug_as_id(): void
    {
        $category = $this->makeCategory();
        $this->makeProduct($category, ['description' => 'Bright yellow', 'extra' => ['halal' => true]]);

        $this->getJson('/api/categories/blended-colours/products/egg-yellow')
            ->assertOk()
            ->assertJsonPath('data.id', 'egg-yellow')
            ->assertJsonPath('data.colorHex', '#eab308')
            ->assertJsonPath('data.description', 'Bright yellow')
            ->assertJsonPath('data.extra.halal', true)
            ->assertJsonPath('data.category.slug', 'blended-colours');
    }

    public function test_product_falls_back_to_category_analysis_specs(): void
    {
        $category = $this->makeCategory();
        $category->syncAnalysisSpecs([['characteristic' => 'Assay', 'requirement' => '85% Min']]);
        $product = $this->makeProduct($category);

        $this->getJson('/api/categories/blended-colours/products/egg-yellow')
            ->assertJsonPath('data.analysis.0.characteristic', 'Assay');

        $product->syncAnalysisSpecs([['characteristic' => 'pH', 'requirement' => '3-4']]);

        $this->getJson('/api/categories/blended-colours/products/egg-yellow')
            ->assertJsonCount(1, 'data.analysis')
            ->assertJsonPath('data.analysis.0.characteristic', 'pH');
    }

    public function test_inactive_products_are_hidden_and_group_key_filters(): void
    {
        $category = $this->makeCategory();
        $this->makeProduct($category, ['group_key' => 'yellow']);
        $this->makeProduct($category, ['name' => 'Red', 'slug' => 'red', 'group_key' => 'red']);
        $this->makeProduct($category, ['name' => 'Off', 'slug' => 'off', 'is_active' => false]);

        $this->getJson('/api/categories/blended-colours/products')->assertJsonCount(2, 'data');
        $this->getJson('/api/categories/blended-colours/products?group_key=red')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 'red');
        $this->getJson('/api/categories/blended-colours/products/off')->assertNotFound();
    }

    public function test_unknown_category_is_404(): void
    {
        $this->getJson('/api/categories/nope')->assertNotFound();
    }

    public function test_seeded_data_has_identical_shape_for_every_category(): void
    {
        $this->seed();

        $productKeys = [];
        $itemKeys = [];
        $attrKeys = [];

        foreach ($this->getJson('/api/categories')->json('data') as $cat) {
            $detail = $this->getJson("/api/categories/{$cat['slug']}")->assertOk()->json('data');
            foreach ($detail['sections'] as $section) {
                foreach ($section['items'] as $item) {
                    $itemKeys[] = implode(',', array_keys($item));
                    foreach ($item['attributes'] as $a) {
                        $attrKeys[] = implode(',', array_keys($a));
                    }
                }
            }

            $products = $this->getJson("/api/categories/{$cat['slug']}/products")->assertOk()->json('data');
            foreach ($products as $p) {
                $productKeys[] = implode(',', array_keys($p));
                foreach ($p['attributes'] as $a) {
                    $attrKeys[] = implode(',', array_keys($a));
                }
                $this->assertIsArray($p['analysis']);
            }
        }

        $this->assertNotEmpty($productKeys);
        $this->assertCount(1, array_unique($productKeys), 'every product must have the same keys');
        $this->assertCount(1, array_unique($itemKeys), 'every section item must have the same keys');
        $this->assertSame(['label,value'], array_values(array_unique($attrKeys)));
    }
}
