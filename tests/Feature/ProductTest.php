<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $user;
    private $shop;

    protected function setUp(): void
    {
        parent::setUp();

        // Admin User (ID 1 for hardcoded controller logic)
        $this->admin = User::create([
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'provider' => 'google',
        ]);

        // Regular User
        $this->user = User::create([
            'id' => 2,
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'provider' => 'google',
        ]);

        // Shop (ID 1 for hardcoded controller logic)
        $this->shop = Shop::create([
            'id' => 1,
            'name' => 'Test Shop',
            'description' => 'Test Description',
            'image_path' => 'test/path.jpg',
        ]);
    }

    public function test_can_list_products()
    {
        Product::create([
            'store_id' => $this->shop->id,
            'created_by' => $this->admin->id,
            'name' => 'Test Product 1',
            'slug' => 'test-product-1',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug']
                ]
            ]);
    }

    public function test_can_view_product()
    {
        $product = Product::create([
            'store_id' => $this->shop->id,
            'created_by' => $this->admin->id,
            'name' => 'Test Product Detail',
            'slug' => 'test-product-detail',
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson(['id' => $product->id, 'name' => 'Test Product Detail']);
    }

    public function test_can_search_products()
    {
        config(['scout.driver' => 'collection']);

        Product::create([
            'store_id' => $this->shop->id,
            'created_by' => $this->admin->id,
            'name' => 'UniqueSearchTerm',
            'slug' => 'unique-search-term',
            'is_active' => true,
        ])->searchable();

        $response = $this->getJson('/api/search?keyword=UniqueSearchTerm');

        $response->assertStatus(200);
        $this->assertGreaterThan(0, count($response->json()));
        $this->assertEquals('UniqueSearchTerm', $response->json()[0]['name']);
    }

    public function test_admin_can_create_product()
    {
        Sanctum::actingAs($this->admin, ['admin']);

        $payload = [
            'name' => 'New Product',
            'description' => 'Description',
            'price' => 50000,
            'stock' => 100,
            'weight' => 200,
            'condition' => 'new',
            // variations are optional in store, if not present it handles single variation logic
        ];

        $response = $this->postJson('/api/products', $payload);

        $response->assertStatus(200)
             ->assertJson(['success' => true]);

        $this->assertDatabaseHas('products', ['name' => 'New Product']);
        // Verify implicit variation creation
        $this->assertDatabaseHas('product_variations', ['price' => 50000]);
    }

    public function test_admin_can_create_product_with_variations()
    {
        Sanctum::actingAs($this->admin, ['admin']);

        $payload = [
            'name' => 'Varied Product',
            'description' => 'Description',
            'condition' => 'new',
            'variations' => [
                [
                    'sku' => 'VAR-1',
                    'price' => 50000,
                    'stock' => 10,
                    'weight' => 200,
                    'attributes' => [
                        ['Color' => 'Red']
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/products', $payload);

        $response->assertStatus(200)
             ->assertJson(['success' => true]);

        $this->assertDatabaseHas('products', ['name' => 'Varied Product']);
        $this->assertDatabaseHas('product_variations', ['sku' => 'VAR-1']);
        $this->assertDatabaseHas('variation_attributes', ['name' => 'Color']);
        $this->assertDatabaseHas('variation_options', ['value' => 'Red']);
    }

    public function test_non_admin_cannot_create_product()
    {
        Sanctum::actingAs($this->user, ['user']); 

        $payload = [
            'name' => 'Unauthorized Product',
            'description' => 'Description',
            'price' => 50000,
            'stock' => 100,
            'weight' => 200,
            'condition' => 'new',
        ];

        $response = $this->postJson('/api/products', $payload);

        $response->assertStatus(403);
    }
    
    public function test_create_product_validation()
    {
        Sanctum::actingAs($this->admin, ['admin']);

        $payload = []; // Empty payload

        $response = $this->postJson('/api/products', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'description', 'condition']);
    }

    public function test_admin_can_update_product()
    {
        Sanctum::actingAs($this->admin, ['admin']);

        $product = Product::create([
            'store_id' => $this->shop->id,
            'created_by' => $this->admin->id,
            'name' => 'Old Name',
            'slug' => 'old-name',
            'is_active' => true,
        ]);
        
        // Ensure variation exists 
        ProductVariation::create([
             'product_id' => $product->id,
             'sku' => 'old-name',
             'price' => 10000,
             'stock' => 10
        ]);

        $payload = [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'price' => 75000,
            'stock' => 50,
            'weight' => 200,
            'condition' => 'new',
        ];

        $response = $this->patchJson("/api/products/{$product->id}", $payload);

        $response->assertStatus(200)
             ->assertJson(['success' => true]);

        $this->assertDatabaseHas('products', ['name' => 'Updated Name']);
    }

    public function test_admin_can_toggle_active()
    {
        Sanctum::actingAs($this->admin, ['admin']);

        $product = Product::create([
            'store_id' => $this->shop->id,
            'created_by' => $this->admin->id,
            'name' => 'Toggle Product',
            'slug' => 'toggle-product',
            'is_active' => true,
        ]);

        $response = $this->patchJson("/api/products/{$product->id}/toggle-active", [
            'is_active' => false
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);
    }
    
    public function test_admin_can_delete_product()
    {
        Sanctum::actingAs($this->admin, ['admin']);

        $product = Product::create([
            'store_id' => $this->shop->id,
            'created_by' => $this->admin->id,
            'name' => 'Delete Product',
            'slug' => 'delete-product',
            'is_active' => true,
        ]);
        
        ProductVariation::create([
             'product_id' => $product->id,
             'sku' => 'delete-product',
             'price' => 10000,
             'stock' => 10
        ]);

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200)
             ->assertJson(['success' => true]);
             
        // Product uses SoftDeletes
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_cannot_view_non_existent_product()
    {
        $response = $this->getJson("/api/products/99999");
        $response->assertStatus(404);
    }
}
