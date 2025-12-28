<?php

namespace Tests\Unit;

use App\Models\CartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_subtotal_uses_discount_price_when_available()
    {
        $item = CartItem::create([
            'cart_id' => 1,
            'product_id' => 1,
            'product_variation_id' => 1,
            'price_at_time' => 10000,
            'price_discount_at_time' => 8000,
            'quantity' => 2,
        ]);
        $this->assertEquals(16000, $item->subtotal);
    }

    public function test_subtotal_uses_regular_price_when_discount_is_null()
    {
        $item = CartItem::create([
            'cart_id' => 1,
            'product_id' => 1,
            'product_variation_id' => 1,
            'price_at_time' => 10000,
            'price_discount_at_time' => null,
            'quantity' => 3,
        ]);
        $this->assertEquals(30000, $item->subtotal);
    }

    public function test_cartitem_relationships()
    {
        $item = CartItem::factory()->create();
        $this->assertNotNull($item->cart);
        $this->assertNotNull($item->product);
        $this->assertNotNull($item->productVariation);
    }
}
