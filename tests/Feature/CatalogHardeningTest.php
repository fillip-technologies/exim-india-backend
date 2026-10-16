<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CatalogHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function category(array $attrs = []): Category
    {
        return Category::create(array_merge(['name' => 'Lake', 'slug' => 'lake'], $attrs));
    }

    public function test_direct_product_lookup_hides_inactive_products_and_categories(): void
    {
        $cat = $this->category();
        Product::create(['category_id' => $cat->id, 'slug' => 'red', 'name' => 'Red', 'is_active' => true]);
        Product::create(['category_id' => $cat->id, 'slug' => 'hidden', 'name' => 'Hidden', 'is_active' => false]);

        $this->getJson('/api/products/red')->assertOk()->assertJsonPath('data.name', 'Red');
        $this->getJson('/api/products/hidden')->assertNotFound();

        $cat->update(['is_active' => false]);
        $this->getJson('/api/products/red')->assertNotFound();
        $this->getJson('/api/products')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_flat_catalogue_falls_back_to_category_specs(): void
    {
        $cat = $this->category();
        $cat->syncAnalysisSpecs([['characteristic' => 'Assay', 'requirement' => '85%']]);
        Product::create(['category_id' => $cat->id, 'slug' => 'red', 'name' => 'Red']);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('data.0.analysis.0.characteristic', 'Assay');
    }

    public function test_admin_can_set_category_navigation_fields(): void
    {
        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));

        $this->postJson('/api/admin/categories', [
            'name' => 'Lake', 'slug' => 'lake', 'show_in_nav' => false, 'nav_label' => 'Lakes',
        ])->assertCreated();

        $this->getJson('/api/categories')->assertJsonPath('data.0.navLabel', 'Lakes');
        $this->getJson('/api/categories?nav=1')->assertJsonCount(0, 'data');
    }

    public function test_admin_contact_shows_order_items(): void
    {
        $cat = $this->category();
        $product = Product::create(['category_id' => $cat->id, 'slug' => 'red', 'name' => 'Red']);

        $this->postJson('/api/orders', [
            'product_id' => $product->id, 'name' => 'Asha', 'email' => 'a@example.com', 'quantity' => '10 Kgs',
            'items' => [['product_id' => $product->id, 'quantity' => '10 Kgs', 'note' => 'Colour: Red']],
        ])->assertCreated();

        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));
        $this->getJson('/api/admin/contacts?type=order')
            ->assertOk()
            ->assertJsonPath('data.0.items.0.label', 'Red')
            ->assertJsonPath('data.0.items.0.note', 'Colour: Red');
    }
}
