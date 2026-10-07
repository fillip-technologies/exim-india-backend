<?php

namespace Tests\Feature;

use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TestimonialApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_list_returns_only_active_in_order(): void
    {
        Testimonial::create(['name' => 'B', 'quote' => 'Great.', 'sort_order' => 2]);
        Testimonial::create(['name' => 'A', 'quote' => 'Good.', 'sort_order' => 1]);
        Testimonial::create(['name' => 'Hidden', 'quote' => 'Nope.', 'is_active' => false]);

        $this->getJson('/api/testimonials')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'A')
            ->assertJsonPath('data.1.name', 'B')
            ->assertJsonMissingPath('data.0.is_active');
    }

    public function test_admin_requires_auth(): void
    {
        $this->getJson('/api/admin/testimonials')->assertUnauthorized();
    }

    public function test_admin_full_crud(): void
    {
        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));

        $created = $this->postJson('/api/admin/testimonials', [
            'name' => 'Jane Doe',
            'role' => 'QA Lead',
            'company' => 'Acme Foods',
            'quote' => 'Excellent partner.',
        ])->assertCreated()->assertJsonPath('name', 'Jane Doe');

        $id = $created->json('id');

        $this->getJson("/api/admin/testimonials/{$id}")->assertOk()->assertJsonPath('company', 'Acme Foods');

        $this->putJson("/api/admin/testimonials/{$id}", ['name' => 'Jane A. Doe', 'is_active' => false])
            ->assertOk()->assertJsonPath('name', 'Jane A. Doe')->assertJsonPath('is_active', false);

        $this->getJson('/api/admin/testimonials')->assertJsonCount(1);

        $this->deleteJson("/api/admin/testimonials/{$id}")->assertNoContent();
        $this->assertDatabaseCount('testimonials', 0);
    }

    public function test_quote_is_required(): void
    {
        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));

        $this->postJson('/api/admin/testimonials', ['name' => 'No Quote'])
            ->assertUnprocessable()->assertJsonValidationErrors('quote');
    }
}
