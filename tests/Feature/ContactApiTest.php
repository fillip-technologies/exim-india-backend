<?php

namespace Tests\Feature;

use App\Mail\ContactReceived;
use App\Models\Category;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    private function contactPayload(array $over = []): array
    {
        return [
            'name' => 'Asha',
            'email' => 'asha@example.com',
            'message' => 'Need a quote.',
            ...$over,
        ];
    }

    public function test_contact_form_is_stored(): void
    {
        $this->postJson('/api/contact', $this->contactPayload(['company' => 'Acme', 'product_interest' => 'Lake Colours']))
            ->assertCreated();

        $this->assertDatabaseHas('contacts', [
            'type' => 'contact', 'email' => 'asha@example.com', 'company' => 'Acme', 'status' => 'new',
        ]);
    }

    public function test_contact_validation_and_honeypot(): void
    {
        $this->postJson('/api/contact', [])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'message']);
        $this->postJson('/api/contact', $this->contactPayload(['website' => 'http://spam']))->assertUnprocessable();
        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_contact_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/contact', $this->contactPayload())->assertCreated();
        }
        $this->postJson('/api/contact', $this->contactPayload())->assertStatus(429);
    }

    public function test_order_requires_existing_product_and_is_linked(): void
    {
        $product = Category::create(['name' => 'Lake', 'slug' => 'lake'])
            ->products()->create(['name' => 'Red', 'slug' => 'red']);

        $this->postJson('/api/orders', ['product_id' => 999, 'name' => 'A', 'email' => 'a@b.co', 'quantity' => '100 Kgs'])
            ->assertUnprocessable()->assertJsonValidationErrors('product_id');

        $this->postJson('/api/orders', ['product_id' => $product->id, 'name' => 'A', 'email' => 'a@b.co', 'quantity' => '100 Kgs', 'address' => 'Mumbai'])
            ->assertCreated();

        $this->assertDatabaseHas('contacts', ['type' => 'order', 'product_id' => $product->id, 'quantity' => '100 Kgs']);
    }

    public function test_notification_mail_only_when_configured(): void
    {
        Mail::fake();

        $this->postJson('/api/contact', $this->contactPayload())->assertCreated();
        Mail::assertNothingQueued();

        config(['mail.contact_notify_to' => 'info@example.com']);
        $this->postJson('/api/contact', $this->contactPayload())->assertCreated();
        Mail::assertQueued(ContactReceived::class, fn ($m) => $m->hasTo('info@example.com'));
    }
}
