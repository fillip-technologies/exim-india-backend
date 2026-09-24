<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'password' => 'secret-pass']);
    }

    public function test_login_returns_token_for_admin_only(): void
    {
        $admin = $this->admin();
        $this->postJson('/api/admin/login', ['email' => $admin->email, 'password' => 'secret-pass'])
            ->assertOk()->assertJsonStructure(['token', 'user']);

        $this->postJson('/api/admin/login', ['email' => $admin->email, 'password' => 'wrong'])->assertUnprocessable();

        $user = User::factory()->create(['is_admin' => false, 'password' => 'secret-pass']);
        $this->postJson('/api/admin/login', ['email' => $user->email, 'password' => 'secret-pass'])->assertUnprocessable();
    }

    public function test_admin_routes_require_admin(): void
    {
        $this->getJson('/api/admin/products')->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create(['is_admin' => false]));
        $this->getJson('/api/admin/products')->assertForbidden();
    }

    public function test_category_and_product_crud_with_specs(): void
    {
        Sanctum::actingAs($this->admin());

        $cat = $this->postJson('/api/admin/categories', [
            'name' => 'Lake', 'slug' => 'lake',
            'analysis_specs' => [['characteristic' => 'Assay', 'requirement' => '85%']],
        ])->assertCreated()->assertJsonPath('analysis_specs.0.characteristic', 'Assay');

        $product = $this->postJson('/api/admin/products', [
            'category_id' => $cat->json('id'), 'name' => 'Red', 'slug' => 'red',
            'analysis' => [['characteristic' => 'pH', 'requirement' => '3-4']],
            'attributes' => [['label' => 'E Number', 'value' => 'E102']],
        ])->assertCreated()->assertJsonPath('detail_attributes.0.label', 'E Number');

        // slug unique within a category
        $this->postJson('/api/admin/products', ['category_id' => $cat->json('id'), 'name' => 'Red2', 'slug' => 'red'])
            ->assertUnprocessable()->assertJsonValidationErrors('slug');

        $this->putJson('/api/admin/products/'.$product->json('id'), ['name' => 'Ruby Red', 'analysis' => []])
            ->assertOk()->assertJsonPath('name', 'Ruby Red')->assertJsonCount(0, 'analysis_specs');

        $this->deleteJson('/api/admin/products/'.$product->json('id'))->assertNoContent();
        $this->assertSoftDeleted('products', ['id' => $product->json('id')]);
    }

    public function test_category_sections_are_synced(): void
    {
        Sanctum::actingAs($this->admin());

        $res = $this->postJson('/api/admin/categories', [
            'name' => 'Disco', 'slug' => 'disco',
            'sections' => [[
                'key' => 'shades', 'title' => 'Shades',
                'items' => [['title' => 'Copper', 'subtitle' => 'Warm', 'color_hex' => '#b45309',
                    'attributes' => [['label' => 'Code', 'value' => '5M']]]],
            ]],
        ])->assertCreated();

        $res->assertJsonPath('sections.0.items.0.detail_attributes.0.value', '5M');

        $this->putJson('/api/admin/categories/disco', ['sections' => []])->assertOk()->assertJsonCount(0, 'sections');
    }

    public function test_contacts_list_filter_and_status_update(): void
    {
        Sanctum::actingAs($this->admin());
        $a = Contact::create(['type' => 'contact', 'name' => 'A', 'email' => 'a@b.co']);
        Contact::create(['type' => 'order', 'name' => 'B', 'email' => 'b@b.co']);

        $this->getJson('/api/admin/contacts?type=order')->assertJsonCount(1, 'data');
        $this->patchJson("/api/admin/contacts/{$a->id}", ['status' => 'read'])->assertJsonPath('data.status', 'read');
        $this->patchJson("/api/admin/contacts/{$a->id}", ['status' => 'bogus'])->assertUnprocessable();
    }

    public function test_image_upload(): void
    {
        \Storage::fake('public');
        Sanctum::actingAs($this->admin());

        $res = $this->postJson('/api/admin/uploads', ['image' => \Illuminate\Http\UploadedFile::fake()->image('p.jpg')])->assertCreated();
        \Storage::disk('public')->assertExists($res->json('path'));
    }
}
