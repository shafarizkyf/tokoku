<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\CheckoutSession;
use App\Models\CheckoutSessionItem;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CheckoutSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_generates_session_id_and_expires_at_on_creation()
    {
        $session = CheckoutSession::create([
            'subtotal' => 100000,
        ]);

        $this->assertNotNull($session->session_id);
        $this->assertNotNull($session->expires_at);
        $this->assertTrue($session->expires_at->isFuture());
        $this->assertGreaterThanOrEqual(23.9, now()->diffInHours($session->expires_at));
    }

    public function test_is_expired_returns_false_for_active_session()
    {
        $session = CheckoutSession::create([
            'expires_at' => now()->addHours(24),
        ]);

        $this->assertFalse($session->isExpired());
    }

    public function test_is_expired_returns_true_for_expired_session()
    {
        $session = CheckoutSession::create([
            'expires_at' => now()->subHour(),
        ]);

        $this->assertTrue($session->isExpired());
    }

    public function test_grand_total_calculates_correctly()
    {
        $session = CheckoutSession::create([
            'subtotal' => 100000,
            'shipping_price' => 15000,
            'discount' => 5000,
        ]);

        $this->assertEquals(110000, $session->grand_total);
    }

    public function test_grand_total_without_discount()
    {
        $session = CheckoutSession::create([
            'subtotal' => 100000,
            'shipping_price' => 15000,
            'discount' => 0,
        ]);

        $this->assertEquals(115000, $session->grand_total);
    }

    public function test_full_address_returns_null_when_no_address()
    {
        $session = CheckoutSession::create();

        $this->assertNull($session->full_address);
    }

    public function test_full_address_returns_complete_address()
    {
        $session = CheckoutSession::create([
            'address_detail' => 'Jl. Test No. 1',
            'postal_code' => 12345,
        ]);

        $address = $session->full_address;

        $this->assertStringContainsString('Jl. Test No. 1', $address);
        $this->assertStringContainsString('12345', $address);
    }

    public function test_mark_as_paid_updates_payment_status()
    {
        $session = CheckoutSession::create([
            'payment_status' => 'pending',
        ]);

        $result = $session->markAsPaid('REF123', ['status' => 'PAID']);

        $this->assertTrue($result);
        $this->assertEquals('paid', $session->payment_status);
        $this->assertEquals('REF123', $session->payment_reference);
        $this->assertNotNull($session->paid_at);
        $this->assertEquals('completed', $session->status);
    }

    public function test_mark_as_failed_updates_payment_status()
    {
        $session = CheckoutSession::create([
            'payment_status' => 'processing',
        ]);

        $result = $session->markAsFailed('REF456', ['status' => 'FAILED']);

        $this->assertTrue($result);
        $this->assertEquals('failed', $session->payment_status);
        $this->assertEquals('REF456', $session->payment_reference);
    }

    public function test_mark_as_failed_without_reference()
    {
        $session = CheckoutSession::create([
            'payment_status' => 'processing',
        ]);

        $result = $session->markAsFailed();

        $this->assertTrue($result);
        $this->assertEquals('failed', $session->payment_status);
        $this->assertNull($session->payment_reference);
    }

    public function test_convert_to_order_fails_when_not_paid()
    {
        $session = CheckoutSession::create([
            'payment_status' => 'pending',
        ]);

        $order = $session->convertToOrder();

        $this->assertNull($order);
    }

    public function test_convert_to_order_creates_order_from_paid_session()
    {
        $session = CheckoutSession::create([
            'user_id' => 1,
            'subtotal' => 100000,
            'total_weight' => 1000,
            'shipping_price' => 15000,
            'discount' => 5000,
            'courier' => 'jne',
            'recipient_name' => 'John Doe',
            'recipient_email' => 'john@example.com',
            'recipient_phone' => '08123456789',
            'address_detail' => 'Jl. Test',
            'province_id' => 1,
            'regency_id' => 1,
            'district_id' => 1,
            'village_id' => 1,
            'postal_code' => 12345,
            'payment_method' => 'BNIVA',
            'payment_status' => 'paid',
            'payment_reference' => 'REF123',
            'payment_response' => json_encode(['data' => ['total_fee' => 2000]]),
            'paid_at' => now(),
        ]);

        $order = $session->convertToOrder();

        $this->assertNotNull($order);
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('paid', $order->status);
        $this->assertEquals('John Doe', $order->recipient_name);
        $this->assertEquals('john@example.com', $order->recipient_email);
        $this->assertEquals(110000, $order->grand_total);
        $this->assertNotNull($session->order_id);
        $this->assertEquals('completed', $session->status);
    }

    public function test_convert_to_order_creates_order_details_from_items()
    {
        $session = CheckoutSession::create([
            'user_id' => 1,
            'subtotal' => 100000,
            'total_weight' => 1000,
            'shipping_price' => 15000,
            'discount' => 0,
            'courier' => 'jne',
            'recipient_name' => 'Test User',
            'recipient_phone' => '08123456789',
            'address_detail' => 'Jl. Test',
            'province_id' => 1,
            'regency_id' => 1,
            'district_id' => 1,
            'village_id' => 1,
            'postal_code' => 12345,
            'payment_method' => 'BNIVA',
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        $product = Product::factory()->create(['id' => 1, 'name' => 'Test Product']);
        $variation = ProductVariation::factory()->create([
            'product_id' => $product->id,
            'price' => 50000,
            'weight' => 500,
        ]);

        $item = CheckoutSessionItem::create([
            'checkout_session_id' => $session->id,
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'quantity' => 2,
            'price_at_time' => 50000,
            'price_discount_at_time' => null,
        ]);

        $order = $session->convertToOrder();

        $this->assertNotNull($order);
        $this->assertEquals(1, $order->orderDetails()->count());
        $this->assertEquals(2, $order->orderDetails->first()->quantity);
        $this->assertEquals(50000, $order->orderDetails->first()->price);
    }

    public function test_link_to_user_updates_user_id()
    {
        $session = CheckoutSession::create();

        $result = $session->linkToUser(123);

        $this->assertTrue($result);
        $this->assertEquals(123, $session->user_id);
    }

    public function test_snapshot_cart_items_creates_items_from_cart()
    {
        $session = CheckoutSession::create();

        $product = Product::factory()->create(['id' => 1]);
        $variation = ProductVariation::factory()->create([
            'product_id' => $product->id,
            'price' => 25000,
            'discount_price' => 20000,
        ]);

        $cart = \App\Models\Cart::factory()->create();
        $cartItem = \App\Models\CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variation_id' => $variation->id,
            'quantity' => 3,
            'price_at_time' => 25000,
            'price_discount_at_time' => 20000,
        ]);

        $session->snapshotCartItems($cart);

        $this->assertEquals(1, $session->items()->count());
        $this->assertEquals(3, $session->items->first()->quantity);
        $this->assertEquals(20000, $session->items->first()->price_discount_at_time);
    }

    public function test_find_by_session_id_returns_active_session()
    {
        $session = CheckoutSession::create([
            'expires_at' => now()->addHours(24),
        ]);

        $found = CheckoutSession::findBySessionId($session->session_id);

        $this->assertNotNull($found);
        $this->assertEquals($session->id, $found->id);
    }

    public function test_find_by_session_id_returns_null_for_expired_session()
    {
        $session = CheckoutSession::create([
            'expires_at' => now()->subHour(),
            'status' => 'active',
        ]);

        $found = CheckoutSession::findBySessionId($session->session_id);

        $this->assertNull($found);
    }

    public function test_find_by_session_id_returns_null_for_cancelled_session()
    {
        $session = CheckoutSession::create([
            'status' => 'cancelled',
        ]);

        $found = CheckoutSession::findBySessionId($session->session_id);

        $this->assertNull($found);
    }

    public function test_find_active_by_user_returns_user_session()
    {
        $session = CheckoutSession::create([
            'user_id' => 1,
            'expires_at' => now()->addHours(24),
        ]);

        $found = CheckoutSession::findActiveByUser(1);

        $this->assertNotNull($found);
        $this->assertEquals($session->id, $found->id);
    }

    public function test_active_scope_filters_by_status_and_expiry()
    {
        CheckoutSession::create([
            'status' => 'active',
            'expires_at' => now()->addHours(24),
        ]);

        CheckoutSession::create([
            'status' => 'expired',
            'expires_at' => now()->addHours(24),
        ]);

        CheckoutSession::create([
            'status' => 'active',
            'expires_at' => now()->subHour(),
        ]);

        $activeCount = CheckoutSession::active()->count();

        $this->assertEquals(1, $activeCount);
    }

    public function test_by_session_scope_filters_by_session_id()
    {
        $session1 = CheckoutSession::create();
        $session2 = CheckoutSession::create();

        $found = CheckoutSession::bySession($session1->session_id)->first();

        $this->assertNotNull($found);
        $this->assertEquals($session1->id, $found->id);
        $this->assertNotEquals($session2->id, $found->id);
    }

    public function test_by_user_scope_filters_by_user_id()
    {
        $session1 = CheckoutSession::create(['user_id' => 1]);
        $session2 = CheckoutSession::create(['user_id' => 2]);

        $found = CheckoutSession::byUser(1)->first();

        $this->assertNotNull($found);
        $this->assertEquals(1, $found->user_id);
    }
}
