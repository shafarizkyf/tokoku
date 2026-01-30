<?php

namespace Tests\Unit;

use App\Models\CheckoutSession;
use App\Models\CheckoutSessionItem;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutSessionItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_subtotal_uses_discount_price_when_available()
    {
        $session = CheckoutSession::create();
        $product = Product::factory()->create(['id' => 1]);
        $variation = ProductVariation::factory()->create([
            'product_id' => $product->id,
        ]);

        $item = CheckoutSessionItem::create([
            'checkout_session_id' => $session->id,
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'price_at_time' => 10000,
            'price_discount_at_time' => 8000,
            'quantity' => 2,
        ]);

        $this->assertEquals(16000, $item->subtotal);
    }

    public function test_subtotal_uses_regular_price_when_discount_is_null()
    {
        $session = CheckoutSession::create();
        $product = Product::factory()->create(['id' => 1]);
        $variation = ProductVariation::factory()->create([
            'product_id' => $product->id,
            'price' => 10000,
        ]);

        $item = CheckoutSessionItem::create([
            'checkout_session_id' => $session->id,
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'price_at_time' => 10000,
            'price_discount_at_time' => null,
            'quantity' => 3,
        ]);

        $this->assertEquals(30000, $item->subtotal);
    }

    public function test_subtotal_calculates_correctly_with_large_quantity()
    {
        $session = CheckoutSession::create();
        $product = Product::factory()->create(['id' => 1]);
        $variation = ProductVariation::factory()->create([
            'product_id' => $product->id,
        ]);

        $item = CheckoutSessionItem::create([
            'checkout_session_id' => $session->id,
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'price_at_time' => 15000,
            'price_discount_at_time' => 12000,
            'quantity' => 10,
        ]);

        $this->assertEquals(120000, $item->subtotal);
    }

    public function test_checkout_session_relationship()
    {
        $session = CheckoutSession::create();
        $product = Product::factory()->create();
        $variation = ProductVariation::factory()->create(['product_id' => $product->id]);

        $item = CheckoutSessionItem::create([
            'checkout_session_id' => $session->id,
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'price_at_time' => 10000,
            'quantity' => 1,
        ]);

        $this->assertNotNull($item->checkoutSession);
        $this->assertEquals($session->id, $item->checkoutSession->id);
    }

    public function test_product_relationship()
    {
        $session = CheckoutSession::create();
        $product = Product::factory()->create();
        $variation = ProductVariation::factory()->create([
            'product_id' => $product->id,
        ]);

        $item = CheckoutSessionItem::create([
            'checkout_session_id' => $session->id,
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'price_at_time' => 10000,
            'quantity' => 1,
        ]);

        $this->assertNotNull($item->product);
        $this->assertEquals($product->id, $item->product->id);
    }

    public function test_product_variation_relationship()
    {
        $session = CheckoutSession::create();
        $product = Product::factory()->create();
        $variation = ProductVariation::factory()->create([
            'product_id' => $product->id,
            'price' => 25000,
        ]);

        $item = CheckoutSessionItem::create([
            'checkout_session_id' => $session->id,
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'price_at_time' => 25000,
            'quantity' => 1,
        ]);

        $this->assertNotNull($item->productVariation);
        $this->assertEquals($variation->id, $item->productVariation->id);
    }

    public function test_multiple_items_on_same_session()
    {
        $session = CheckoutSession::create();
        $product1 = Product::factory()->create(['id' => 1]);
        $product2 = Product::factory()->create(['id' => 2]);
        $variation1 = ProductVariation::factory()->create(['product_id' => $product1->id]);
        $variation2 = ProductVariation::factory()->create(['product_id' => $product2->id]);

        CheckoutSessionItem::create([
            'checkout_session_id' => $session->id,
            'product_id' => $product1->id,
            'product_variation_id' => $variation1->id,
            'price_at_time' => 10000,
            'quantity' => 2,
        ]);

        CheckoutSessionItem::create([
            'checkout_session_id' => $session->id,
            'product_id' => $product2->id,
            'product_variation_id' => $variation2->id,
            'price_at_time' => 20000,
            'quantity' => 1,
        ]);

        $this->assertEquals(2, $session->items()->count());
        $this->assertEquals(40000, $session->items->sum('subtotal'));
    }

    public function test_cascade_delete_when_session_deleted()
    {
        $session = CheckoutSession::create();
        $product = Product::factory()->create(['id' => 1]);
        $variation = ProductVariation::factory()->create(['product_id' => $product->id]);

        CheckoutSessionItem::create([
            'checkout_session_id' => $session->id,
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'price_at_time' => 10000,
            'quantity' => 1,
        ]);

        $this->assertEquals(1, CheckoutSessionItem::count());

        $session->delete();

        $this->assertEquals(0, CheckoutSessionItem::count());
    }

    public function test_fillable_attributes()
    {
        $session = CheckoutSession::create();
        $product = Product::factory()->create(['id' => 1]);
        $variation = ProductVariation::factory()->create(['product_id' => $product->id]);

        $item = new CheckoutSessionItem([
            'checkout_session_id' => $session->id,
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'quantity' => 5,
            'price_at_time' => 15000,
            'price_discount_at_time' => 12000,
        ]);

        $this->assertEquals($session->id, $item->checkout_session_id);
        $this->assertEquals($product->id, $item->product_id);
        $this->assertEquals($variation->id, $item->product_variation_id);
        $this->assertEquals(5, $item->quantity);
        $this->assertEquals(15000, $item->price_at_time);
        $this->assertEquals(12000, $item->price_discount_at_time);
    }
}
