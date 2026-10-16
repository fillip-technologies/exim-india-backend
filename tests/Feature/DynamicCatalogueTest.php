<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Contact;
use App\Models\ContactItem;
use App\Models\FieldDefinition;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\CatalogueSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicCatalogueTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        $this->seed(AdminSeeder::class);
        $user = User::where('is_admin', true)->first();

        return $user->createToken('test')->plainTextToken;
    }

    public function test_nav_filter_returns_only_navbar_categories(): void
    {
        $this->seed([CategorySeeder::class]);

        $allRes = $this->getJson('/api/categories')->assertOk();
        $this->assertCount(21, $allRes->json('data'));

        $navRes = $this->getJson('/api/categories?nav=1')->assertOk();
        $this->assertCount(19, $navRes->json('data'));

        $slugs = collect($navRes->json('data'))->pluck('slug')->all();
        $this->assertContains('bakery-colors', $slugs);
        $this->assertNotContains('mango-pulp', $slugs);
        $this->assertNotContains('fruit-fragrance', $slugs);
    }

    public function test_bakery_product_detail_returns_options_variants_and_points(): void
    {
        $this->seed([CategorySeeder::class, ProductSeeder::class, CatalogueSeeder::class]);

        $res = $this->getJson('/api/categories/bakery-colors/products/luster-dust')
            ->assertOk()
            ->json('data');

        $this->assertSame('luster-dust', $res['id']);
        $this->assertSame('Luster Dust', $res['name']);
        $this->assertSame('multi_axis', $res['variantMode']);
        $this->assertNotNull($res['defaultVariantId']);

        // Assert points (applications & techniques)
        $this->assertNotEmpty($res['applications']);
        $this->assertContains('Chocolates & Bonbons', $res['applications']);
        $this->assertNotEmpty($res['techniques']);
        $this->assertSame('Wet Painting (Evapurex Mix)', $res['techniques'][0]['title']);

        // Assert options
        $this->assertCount(2, $res['options']);
        $optionCodes = collect($res['options'])->pluck('code')->all();
        $this->assertContains('colour', $optionCodes);
        $this->assertContains('pack_size', $optionCodes);

        $colourOption = collect($res['options'])->firstWhere('code', 'colour');
        $this->assertCount(18, $colourOption['values']);

        $packOption = collect($res['options'])->firstWhere('code', 'pack_size');
        $this->assertCount(4, $packOption['values']);

        // Assert variants (18 * 4 = 72)
        $this->assertCount(72, $res['variants']);
        $firstVariant = $res['variants'][0];
        $this->assertArrayHasKey('sku', $firstVariant);
        $this->assertArrayHasKey('options', $firstVariant);
        $this->assertArrayHasKey('colour', $firstVariant['options']);
        $this->assertArrayHasKey('pack_size', $firstVariant['options']);
    }

    public function test_chemical_products_have_zero_variant_axes(): void
    {
        $this->seed([CategorySeeder::class, CatalogueSeeder::class]);

        $res = $this->getJson('/api/categories/chemicals/products/diethanolamine-dea')
            ->assertOk()
            ->json('data');

        $this->assertSame('diethanolamine-dea', $res['id']);
        $this->assertSame('DEA', $res['shortName']);
        $this->assertSame('111-42-2', $res['casNo']);
        $this->assertSame('none', $res['variantMode']);
        $this->assertEmpty($res['options']);
        $this->assertEmpty($res['variants']);

        // Assert custom field values
        $this->assertNotEmpty($res['fields']);
        $identificationGroup = collect($res['fields'])->firstWhere('group', 'Identification');
        $this->assertNotNull($identificationGroup);
        $casItem = collect($identificationGroup['items'])->firstWhere('code', 'cas_no');
        $this->assertSame('111-42-2', $casItem['value']);
    }

    public function test_multiline_order_stores_contact_items(): void
    {
        $this->seed([CategorySeeder::class, ProductSeeder::class, CatalogueSeeder::class]);

        $lusterDust = Product::where('slug', 'luster-dust')->firstOrFail();
        $variants = $lusterDust->variants()->take(2)->get();

        $payload = [
            'name'     => 'Pastry Chef John',
            'email'    => 'chef@bakery.com',
            'phone'    => '+91 9999999999',
            'address'  => 'Mumbai, India',
            'items'    => [
                [
                    'variant_id' => $variants[0]->id,
                    'quantity'   => '5',
                    'note'       => 'Urgent batch for wedding',
                ],
                [
                    'variant_id' => $variants[1]->id,
                    'quantity'   => '10',
                    'note'       => 'Standard batch',
                ],
            ],
        ];

        $res = $this->postJson('/api/orders', $payload)->assertStatus(201);
        $res->assertJsonFragment(['message' => 'Thank you! Your order inquiry has been received.']);

        $contact = Contact::where('email', 'chef@bakery.com')->firstOrFail();
        $this->assertCount(2, $contact->items);

        $firstItem = $contact->items[0];
        $this->assertSame($variants[0]->id, $firstItem->variant_id);
        $this->assertSame('5', $firstItem->quantity);
        $this->assertSame('Urgent batch for wedding', $firstItem->note);
        $this->assertStringContainsString('Luster Dust', $firstItem->label_snapshot);
    }

    public function test_admin_can_manage_attributes_and_values(): void
    {
        $token = $this->adminToken();

        // 1. Create attribute
        $attrRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/attributes', [
                'code'       => 'finish',
                'name'       => 'Surface Finish',
                'input_type' => 'text',
                'sort_order' => 10,
            ])->assertStatus(201);

        $attrId = $attrRes->json('id');

        // 2. Add value to attribute
        $valRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/admin/attributes/{$attrId}/values", [
                'label'      => 'Matte Velvet',
                'sort_order' => 1,
            ])->assertStatus(201);

        $valId = $valRes->json('id');
        $this->assertSame('Matte Velvet', $valRes->json('label'));

        // 3. List values
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/admin/attributes/{$attrId}/values")
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_admin_product_variant_generation_cartesian_grid(): void
    {
        $token = $this->adminToken();

        $cat = Category::create([
            'name' => 'Demo Category',
            'slug' => 'demo-category',
            'sort_order' => 99,
        ]);

        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Custom Macaron Glitter',
            'slug' => 'custom-macaron-glitter',
            'moq'  => '500 gm',
        ]);

        $attrColor = Attribute::create([
            'code' => 'glitter_color',
            'name' => 'Glitter Color',
            'input_type' => 'swatch',
        ]);

        $attrSize = Attribute::create([
            'code' => 'glitter_size',
            'name' => 'Glitter Size',
            'input_type' => 'text',
        ]);

        $valRuby = AttributeValue::create(['attribute_id' => $attrColor->id, 'slug' => 'ruby', 'label' => 'Ruby Red', 'hex' => '#c0392b']);
        $valGold = AttributeValue::create(['attribute_id' => $attrColor->id, 'slug' => 'gold', 'label' => 'Gold', 'hex' => '#ffd700']);
        $valSmall = AttributeValue::create(['attribute_id' => $attrSize->id, 'slug' => '1mm', 'label' => '1mm']);
        $valLarge = AttributeValue::create(['attribute_id' => $attrSize->id, 'slug' => '2mm', 'label' => '2mm']);

        // Configure product options (2 colors x 2 sizes = 4 variants expected)
        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/admin/products/{$product->id}/options", [
                'variant_mode' => 'multi_axis',
                'options' => [
                    [
                        'attribute_id' => $attrColor->id,
                        'is_variant_axis' => true,
                        'values' => [
                            ['attribute_value_id' => $valRuby->id],
                            ['attribute_value_id' => $valGold->id],
                        ],
                    ],
                    [
                        'attribute_id' => $attrSize->id,
                        'is_variant_axis' => true,
                        'values' => [
                            ['attribute_value_id' => $valSmall->id],
                            ['attribute_value_id' => $valLarge->id],
                        ],
                    ],
                ],
            ])->assertOk();

        // Generate variants
        $genRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/admin/products/{$product->id}/variants/generate")
            ->assertOk();

        $this->assertSame(4, $genRes->json('created'));
        $this->assertSame(4, $product->variants()->count());

        // Re-generating skips duplicates
        $genAgain = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/admin/products/{$product->id}/variants/generate")
            ->assertOk();
        $this->assertSame(0, $genAgain->json('created'));
    }

    public function test_admin_custom_fields_assignments_and_product_values(): void
    {
        $token = $this->adminToken();

        $cat = Category::create([
            'name' => 'Herbal Category',
            'slug' => 'herbal-category',
            'sort_order' => 88,
        ]);

        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Organic Spirulina Powder',
            'slug' => 'organic-spirulina-powder',
        ]);

        // 1. Create field definition
        $fieldRes = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/fields', [
                'code'       => 'chlorophyll_content',
                'label'      => 'Chlorophyll Content',
                'type'       => 'number',
                'unit'       => '%',
                'group_name' => 'Technical Specs',
            ])->assertStatus(201);

        $fieldId = $fieldRes->json('id');

        // 2. Assign to category
        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/admin/fields/{$fieldId}/assignments", [
                'assignments' => [
                    [
                        'category_id' => $cat->id,
                        'is_required' => true,
                    ],
                ],
            ])->assertOk();

        // 3. Save value on product
        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/admin/products/{$product->id}/fields", [
                'fields' => [
                    'chlorophyll_content' => 2.45,
                ],
            ])->assertOk();

        // 4. Verify public detail API outputs this field under its group
        $detailRes = $this->getJson("/api/categories/{$cat->slug}/products/{$product->slug}")
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($detailRes['fields']);
        $group = collect($detailRes['fields'])->firstWhere('group', 'Technical Specs');
        $this->assertNotNull($group);
        $item = collect($group['items'])->firstWhere('code', 'chlorophyll_content');
        $this->assertEquals(2.45, $item['value']);
        $this->assertSame('%', $item['unit']);
    }
}
