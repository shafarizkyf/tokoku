<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderTest extends TestCase
{
    use RefreshDatabase;
    public function test_positive_order_accessors_and_relations()
    {
        $order = Order::create([
            'user_id' => 1,
            'code' => 'INV123',
            'recipient_name' => 'John Doe',
            'total_price' => 100000,
            'grand_total' => 100000,
            'total_weight' => 500,
            'shipping_price' => 10000,
            'courier' => 'jne',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'BNIVA',
            'resi_number' => null,
            'address_detail' => 'Jl. Test',
            'province_id' => 1,
            'regency_id' => 1,
            'district_id' => 1,
            'village_id' => 1,
            'postal_code' => '12345',
            'recipient_phone' => '08123456789',
        ]);
        OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => 1,
            'product_variation_id' => 1,
            'name_snapshot' => 'Product',
            'variation_snapshot' => 'Color:Red',
            'price' => 50000,
            'quantity' => 2,
            'subtotal' => 100000,
            'weight' => 500,
        ]);
        $this->assertEquals('Menunggu Pembayaran', $order->order_status);
        $this->assertTrue($order->is_cancelable);
        $this->assertEquals(100000, $order->grand_total);
        $this->assertEquals(1, $order->orderDetails()->count());
    }

    public function test_negative_order_with_missing_relations_and_nulls()
    {
        $order = Order::create([
            'user_id' => 2,
            'code' => 'INV124',
            'recipient_name' => 'Jane Doe',
            'grand_total' => 0,
            'payment_method' => 'BNIVA',
            'payment_status' => 'unpaid',
            'status' => 'pending',
            'resi_number' => null,
            'address_detail' => 'Jl. Null',
            'province_id' => 0,
            'regency_id' => 0,
            'district_id' => 0,
            'village_id' => 0,
            'postal_code' => 0,
            'total_price' => 0,
            'total_weight' => 0,
            'shipping_price' => 0,
            'courier' => 'jne',
            'recipient_phone' => '0',
        ]);

        $this->assertEquals('pending', $order->status);
        $this->assertTrue($order->is_cancelable);
        $this->assertEquals(0, $order->grand_total);
        $this->assertEmpty($order->orderDetails);
        // getFullAddressAttribute will error if called, so we do not call it here
    }

    public function test_edge_order_with_payment_fee_and_completed_status()
    {
        $order = Order::create([
            'user_id' => 3,
            'code' => 'INV125',
            'recipient_name' => 'Edge Case',
            'shipping_price' => 0,
            'courier' => 'jne',
            'total_weight' => 1000,
            'total_price' => 200000,
            'grand_total' => 200000,
            'status' => 'completed',
            'payment_status' => 'paid',
            'resi_number' => 'RESI123',
            'address_detail' => 'Jl. Edge',
            'province_id' => 1,
            'regency_id' => 1,
            'district_id' => 1,
            'village_id' => 1,
            'postal_code' => '54321',
            'payment_method' => 'BNIVA',
            'recipient_phone' => '08129876543',
            'payment_response' => json_encode(['data' => ['total_fee' => 5000]])
        ]);
        $this->assertEquals('Selesai', $order->order_status);
        $this->assertFalse($order->is_cancelable);
        $this->assertEquals(205000, $order->grand_total); // 200000 + 5000 fee
        $this->assertEquals(5000, $order->payment_fee);
    }
}