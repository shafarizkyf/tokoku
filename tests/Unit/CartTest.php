<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariation;
use Illuminate\Container\Attributes\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log as FacadesLog;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_weight_and_value_returns_correct_values()
    {
        $userId = 1;
        $cart = Cart::create(['user_id' => $userId]);

        $variation1 = ProductVariation::create([
            'price' => 10000,
            'discount_price' => 8000,
            'weight' => 500
        ]);

        $variation2 = ProductVariation::create([
            'price' => 20000,
            'discount_price' => null,
            'weight' => 1000
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => 1,
            'product_variation_id' => $variation1->id,
            'quantity' => 2,
            'price_at_time' => 10000,
            'price_discount_at_time' => 8000
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => 2,
            'product_variation_id' => $variation2->id,
            'quantity' => 1,
            'price_at_time' => 20000,
            'price_discount_at_time' => null
        ]);

        $result = Cart::calculateWeightAndValue($userId);
        FacadesLog::debug('Cart Calculation Result', $result);

        // (500*2 + 1000*1) = 2000 grams = 2 kg
        $this->assertEquals(2, $result['weight_in_kg']);
        // (8000*2 + 20000*1) = 36000
        $this->assertEquals(36000, $result['package_value']);
    }

    public function test_calculate_weight_and_value_returns_zero_for_empty_cart() {
        $userId = 2;
        Cart::create(['user_id' => $userId]);
        $result = Cart::calculateWeightAndValue($userId);
        $this->assertEquals(0, $result['weight_in_kg']);
        $this->assertEquals(0, $result['package_value']);
    }

    public function test_calculate_weight_and_value_handles_missing_product_variation() {
        $userId = 3;
        $cart = Cart::create(['user_id' => $userId]);
        // CartItem with no valid productVariation (simulate missing relation)
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => 3,
            'product_variation_id' => 9999, // non-existent
            'quantity' => 2,
            'price_at_time' => 15000,
            'price_discount_at_time' => null
        ]);
        $result = Cart::calculateWeightAndValue($userId);
        // Should fallback to default weight (500) and price_at_time
        $this->assertEquals(1, $result['weight_in_kg']); // 500*2=1000g=1kg
        $this->assertEquals(30000, $result['package_value']); // 15000*2
    }
}
