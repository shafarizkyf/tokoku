<?php

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_subtotal_uses_discount_price_when_available()
    {
        $cart = Cart::factory()->create();
        $product = Product::factory()->create(['id' => 1]);
        $productVariation = ProductVariation::factory()->create([
            'product_id' => $product->id,
        ]);

        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variation_id' => $productVariation->id,
            'price_at_time' => 10000,
            'price_discount_at_time' => 8000,
            'quantity' => 2,
        ]);
        $this->assertEquals(16000, $item->subtotal);
    }

    public function test_subtotal_uses_regular_price_when_discount_is_null()
    {
        $cart = Cart::factory()->create();
        $product = Product::factory()->create(['id' => 1]);
        $productVariation = ProductVariation::factory()->create([
            'product_id' => $product->id,
            'price' => 10000,
        ]);

        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variation_id' => $productVariation->id,
            'price_at_time' => $productVariation->price,
            'price_discount_at_time' => null,
            'quantity' => 3,
        ]);

        $this->assertEquals(30000, $item->subtotal);
    }

    public function test_cartitem_relationships()
    {
        $cart = Cart::factory()->create();
        $item = CartItem::factory()->create([
            'cart_id' => $cart->id,
        ]);
        $this->assertNotNull($item->cart);
        $this->assertNotNull($item->product);
        $this->assertNotNull($item->productVariation);
    }
}
