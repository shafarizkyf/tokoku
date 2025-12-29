<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use Illuminate\Container\Attributes\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log as FacadesLog;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_weight_and_value_returns_correct_values()
    {
        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id]);

        $product = Product::factory()->create(['id' => 1]);

        $variation1 = ProductVariation::create([
            'product_id' => $product->id,
            'price' => 10000,
            'discount_price' => 8000,
            'weight' => 500
        ]);

        $variation2 = ProductVariation::create([
            'product_id' => $product->id,
            'price' => 20000,
            'discount_price' => null,
            'weight' => 1000
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variation_id' => $variation1->id,
            'quantity' => 2,
            'price_at_time' => 10000,
            'price_discount_at_time' => 8000
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variation_id' => $variation2->id,
            'quantity' => 1,
            'price_at_time' => 20000,
            'price_discount_at_time' => null
        ]);

        $result = Cart::calculateWeightAndValue($user->id);
        FacadesLog::debug('Cart Calculation Result', $result);

        // (500*2 + 1000*1) = 2000 grams = 2 kg
        $this->assertEquals(2, $result['weight_in_kg']);
        // (8000*2 + 20000*1) = 36000
        $this->assertEquals(36000, $result['package_value']);
    }

    public function test_calculate_weight_and_value_returns_zero_for_empty_cart() {
        $user = User::factory()->create();
        Cart::create(['user_id' => $user->id]);
        $result = Cart::calculateWeightAndValue($user->id);
        $this->assertEquals(0, $result['weight_in_kg']);
        $this->assertEquals(0, $result['package_value']);
    }

    public function test_calculate_weight_and_value_handles_missing_product_variation() {
        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id]);

        $product = Product::factory()->create();

        $price = 15000;
        $variation = ProductVariation::factory()->create([
            'product_id' => $product->id,
            'price' => $price,
        ]);

        $variationId = $variation->id;

        $variation->delete();

        // CartItem with no valid productVariation (simulate missing relation)
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variation_id' => $variationId,
            'quantity' => 2,
            'price_at_time' => $price,
            'price_discount_at_time' => null
        ]);

        $result = Cart::calculateWeightAndValue($user->id);

        // Should fallback to default weight (500) and price_at_time
        $this->assertEquals(1, $result['weight_in_kg']); // 500*2=1000g=1kg
        $this->assertEquals(30000, $result['package_value']); // 15000*2
    }
}
