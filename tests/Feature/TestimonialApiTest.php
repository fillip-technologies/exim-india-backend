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

    public function test_lists_only_active_testimonials_in_order(): void
    {
        Testimonial::create(['name' => 'B', 'quote' => 'Second', 'sort_order' => 2]);
        Testimonial::create(['name' => 'A', 'quote' => 'First', 'sort_order' => 1, 'avatar' => 'testimonials/a.jpg']);
        Testimonial::create(['name' => 'Hidden', 'quote' => 'Nope', 'is_active' => false]);

        $this->getJson('/api/testimonials')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'A')
            ->assertJsonPath('data.0.rating', 5)
            ->assertJsonPath('data.1.name', 'B')
            ->assertJsonStructure(['data' => [['id', 'name', 'designation', 'company', 'location', 'quote', 'avatar', 'rating']]]);
    }

    public function test_admin_testimonial_crud(): void
    {
        $this->getJson('/api/admin/testimonials')->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));

        $this->postJson('/api/admin/testimonials', ['name' => 'No quote'])
            ->assertUnprocessable()->assertJsonValidationErrors('quote');
        $this->postJson('/api/admin/testimonials', ['name' => 'X', 'quote' => 'Q', 'rating' => 6])
            ->assertUnprocessable()->assertJsonValidationErrors('rating');

        $id = $this->postJson('/api/admin/testimonials', [
            'name' => 'Marcus Vance',
            'designation' => 'Director of Global Procurement',
            'company' => 'BevTech Innovations Europe',
            'location' => 'Frankfurt, Germany',
            'quote' => 'Great batch consistency.',
        ])->assertCreated()->json('id');

        $this->patchJson("/api/admin/testimonials/{$id}", ['is_active' => false])
            ->assertOk()->assertJsonPath('is_active', false)->assertJsonPath('name', 'Marcus Vance');
        $this->getJson('/api/testimonials')->assertJsonCount(0, 'data');
        $this->getJson('/api/admin/testimonials')->assertJsonCount(1);

        $this->deleteJson("/api/admin/testimonials/{$id}")->assertNoContent();
        $this->assertDatabaseCount('testimonials', 0);
    }
}
