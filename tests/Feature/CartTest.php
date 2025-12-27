<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ProductVariationOption;
use App\Models\User;
use App\Models\VariationAttribute;
use App\Models\VariationOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $product;
    private $variation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'provider' => 'google'
        ]);
        Sanctum::actingAs($this->user);

        $this->product = Product::create([
            'store_id' => $this->user->id,
            'created_by' => $this->user->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'is_active' => true,
        ]);

        $this->variation = ProductVariation::create([
            'product_id' => $this->product->id,
            'price' => 10000,
            'stock' => 10,
            'weight' => 500,
        ]);

        // Setup variation options necessary for resource loading
        $attribute = VariationAttribute::create(['name' => 'Color']);
        $option = VariationOption::create(['variation_attribute_id' => $attribute->id, 'value' => 'Red']);
        ProductVariationOption::create([
            'product_variation_id' => $this->variation->id,
            'variation_option_id' => $option->id
        ]);
    }

    public function test_user_can_get_cart_items()
    {
        $cart = Cart::create(['user_id' => $this->user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'product_variation_id' => $this->variation->id,
            'quantity' => 2,
            'price_at_time' => 10000,
        ]);

        $response = $this->getJson('/api/carts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'cart_item_id',
                    'product_name',
                    'quantity',
                    'price',
                    'subtotal'
                ]
            ]);

        $this->assertEquals(2, $response->json()[0]['quantity']);
    }

    public function test_user_can_add_item_to_cart()
    {
        $payload = [
            'product_id' => $this->product->id,
            'product_variation_id' => $this->variation->id,
            'quantity' => 2
        ];

        $response = $this->postJson('/api/carts', $payload);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'product_variation_id' => $this->variation->id,
            'quantity' => 2
        ]);
    }

    public function test_user_cannot_add_more_than_stock()
    {
        $payload = [
            'product_id' => $this->product->id,
            'product_variation_id' => $this->variation->id,
            'quantity' => 100 // Exceeds stock of 10
        ];

        $response = $this->postJson('/api/carts', $payload);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    public function test_user_can_update_cart_item_quantity()
    {
        $cart = Cart::create(['user_id' => $this->user->id]);
        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'product_variation_id' => $this->variation->id,
            'quantity' => 1,
            'price_at_time' => 10000,
        ]);

        $response = $this->patchJson("/api/carts/items/{$cartItem->id}", [
            'quantity' => 5
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 5
        ]);
    }

    public function test_user_can_remove_item_from_cart()
    {
        $cart = Cart::create(['user_id' => $this->user->id]);
        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'product_variation_id' => $this->variation->id,
            'quantity' => 1,
            'price_at_time' => 10000,
        ]);

        $response = $this->deleteJson("/api/carts/items/{$cartItem->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
    }

    public function test_user_can_get_cart_count()
    {
        $cart = Cart::create(['user_id' => $this->user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'product_variation_id' => $this->variation->id,
            'quantity' => 3,
            'price_at_time' => 10000,
        ]);

        // Route: /api/carts/count
        $response = $this->getJson('/api/carts/count');

        $response->assertStatus(200)
             ->assertJson(['items_count' => 1]); // 1 item type
    }
}
