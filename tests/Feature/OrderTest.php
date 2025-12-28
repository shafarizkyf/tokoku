<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\District;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ProductVariationOption;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Shop;
use App\Models\User;
use App\Models\VariationAttribute;
use App\Models\VariationOption;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $product;
    private $variation;
    private $province;
    private $regency;
    private $district;
    private $village;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'provider' => 'google',
        ]);

        Sanctum::actingAs($this->user);

        // Setup Shop
        $shop = Shop::create([
            'id' => 1,
            'name' => 'Test Shop',
            'description' => 'Test Description',
            'image_path' => 'test/path.jpg',
        ]);

        // Setup Product
        $this->product = Product::create([
            'store_id' => $shop->id,
            'created_by' => 1,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'is_active' => true,
        ]);

        $this->variation = ProductVariation::create([
            'product_id' => $this->product->id,
            'sku' => 'SKU-001',
            'price' => 10000,
            'stock' => 10,
            'weight' => 500,
        ]);
        
        $attribute = VariationAttribute::create(['name' => 'Color']);
        $option = VariationOption::create(['variation_attribute_id' => $attribute->id, 'value' => 'Red']);
        ProductVariationOption::create([
            'product_variation_id' => $this->variation->id,
            'variation_option_id' => $option->id
        ]);

        // Setup Locations
        $this->province = Province::create(['id' => 11, 'name' => 'Aceh']);
        $this->regency = Regency::create(['id' => 1101, 'province_id' => 11, 'name' => 'Kab. Aceh Selatan']);
        $this->district = District::create(['id' => 1101010, 'regency_id' => 1101, 'name' => 'Bakongan']);
        $this->village = Village::create(['id' => 1101010001, 'district_id' => 1101010, 'name' => 'Keude Bakongan']);
    }

    public function test_user_can_create_order()
    {
        // Mock External APIs
        Http::fake([
            // Komerce destination search
            '*/tariff/api/v1/destination/search*' => Http::response([
                'status' => true,
                'data' => [
                    ['id' => 123, 'label' => 'Test Destination']
                ]
            ], 200),
            // Komerce calculate
            '*/tariff/api/v1/calculate*' => Http::response([
                'status' => true,
                'data' => [
                    'REG' => [
                        [
                            'shipping_name' => 'JNE',
                            'service_name' => 'REG',
                            'shipping_cost' => 10000,
                            'etd' => '1-2 Days'
                        ]
                    ]
                ]
            ], 200),
            // Tripay
            '*/transaction/create' => Http::response([
                'success' => true,
                'message' => 'Success',
                'data' => ['reference' => 'REF123', 'total_fee' => 1000]
            ], 200),
            // WhatsApp
            '*/messages' => Http::response(['success' => true], 200),
        ]);

        // Setup Cart
        $cart = Cart::create(['user_id' => $this->user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'product_variation_id' => $this->variation->id,
            'quantity' => 1,
            'price_at_time' => 10000,
        ]);

        $payload = [
            'items' => [
                [
                    'product_variation_id' => $this->variation->id,
                    'quantity' => 1
                ]
            ],
            'payment_method' => 'MY_PAYMENT',
            'shipping' => [
                'receiver_name' => 'John Doe',
                'phone_number' => '08123456789',
                'address' => 'Jalan Test',
                'province_id' => $this->province->id,
                'regency_id' => $this->regency->id,
                'district_id' => $this->district->id,
                'village_id' => $this->village->id,
                'postal_code' => '12345',
                'note' => 'Test Note'
            ],
            'delivery' => [
                'shipping_name' => 'JNE',
                'service_name' => 'REG'
            ]
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
            
        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'grand_total' => 20000, // 10000 item + 10000 shipping
            'recipient_name' => 'John Doe',
        ]);
        
        // Stock should decrease
        $this->assertDatabaseHas('product_variations', [
            'id' => $this->variation->id,
            'stock' => 9
        ]);
        
        // Cart should be empty
        $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id]);
    }

    public function test_create_order_failed_if_no_stock()
    {
        $this->variation->update(['stock' => 0]);
        
        $payload = [
            'items' => [
                [
                    'product_variation_id' => $this->variation->id,
                    'quantity' => 1
                ]
            ],
            // other fields not validated yet because stock check fails in array validation rules
            // but for completeness we add structure
             'payment_method' => 'MY_PAYMENT',
            'shipping' => [
                'receiver_name' => 'John Doe',
                'phone_number' => '08123456789',
                'address' => 'Jalan Test',
                'province_id' => $this->province->id,
                'regency_id' => $this->regency->id,
                'district_id' => $this->district->id,
                'village_id' => $this->village->id,
                'postal_code' => '12345',
            ],
            'delivery' => [
                'shipping_name' => 'JNE',
                'service_name' => 'REG'
            ]
        ];
        
        $response = $this->postJson('/api/orders', $payload);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity']);
    }

    public function test_user_can_list_orders()
    {
        Order::create([
            'user_id' => $this->user->id,
            'code' => 'INV-TEST-001',
            'status' => 'pending',
            'payment_method' => 'manual',
            'total_price' => 10000,
            'total_weight' => 500,
            'shipping_price' => 5000,
            'grand_total' => 15000,
            'courier' => 'JNE - REG',
            'recipient_name' => 'John Doe',
            'recipient_phone' => '08123',
            'address_detail' => 'Test Addr',
            'province_id' => $this->province->id,
            'regency_id' => $this->regency->id,
            'district_id' => $this->district->id,
            'village_id' => $this->village->id,
            'postal_code' => 12345,
        ]);

        $response = $this->getJson('/api/orders?draw=1&start=0&length=10&search[value]=');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'code', 'grand_total', 'status']
                ]
            ]);
    }

    public function test_user_can_cancel_order()
    {
        $order = Order::create([
            'user_id' => $this->user->id,
            'code' => 'INV-TEST-MCANCEL',
            'status' => 'pending',
            'payment_method' => 'BNIVA',
            'total_price' => 10000,
            'total_weight' => 500,
            'shipping_price' => 5000,
            'grand_total' => 15000,
            'courier' => 'JNE - REG',
            'recipient_name' => 'John Doe',
            'recipient_phone' => '08123',
            'address_detail' => 'Test Addr',
            'province_id' => $this->province->id,
            'regency_id' => $this->regency->id,
            'district_id' => $this->district->id,
            'village_id' => $this->village->id,
            'postal_code' => 12345,
            'payment_response' => json_encode([
                'data' => [
                    'total_fee' => 0
                ]
            ])
        ]);

        $response = $this->patchJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
            
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled'
        ]);
    }

    public function test_user_cannot_cancel_shipped_order()
    {
        $order = Order::create([
            'user_id' => $this->user->id,
            'code' => 'INV-TEST-NCANCEL',
            'status' => 'shipped',
            'payment_method' => 'manual',
            'total_price' => 10000,
            'total_weight' => 500,
            'shipping_price' => 5000,
            'grand_total' => 15000,
            'courier' => 'JNE - REG',
            'recipient_name' => 'John Doe',
            'recipient_phone' => '08123',
            'address_detail' => 'Test Addr',
            'province_id' => $this->province->id,
            'regency_id' => $this->regency->id,
            'district_id' => $this->district->id,
            'village_id' => $this->village->id,
            'postal_code' => 12345,
        ]);

        $response = $this->patchJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
            
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'shipped'
        ]);
    }
    public function test_order_fails_with_insufficient_stock()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Product::factory()->create();
        $variation = ProductVariation::factory()->create([
            'product_id' => $product->id,
            'stock' => 1,
            'price' => 100000,
        ]);

        $payload = [
            'items' => [
                [
                    'product_variation_id' => $variation->id,
                    'quantity' => 2
                ]
            ],
            'payment_method' => 'BNIVA',
            'shipping' => [
                'receiver_name' => 'Test User',
                'province_id' => '33',
                'regency_id' => '3302',
                'district_id' => '330227',
                'village_id' => '3302271003',
                'postal_code' => '53125',
                'address' => 'Jl. Test',
                'note' => 'Test note',
                'phone_number' => '08123456789',
            ],
            'delivery' => [
                'shipping_name' => 'JNE',
                'service_name' => 'JNEReg',
            ]
        ];

        $response = $this->postJson('/api/orders', $payload);
        $response->assertStatus(422);
    }
}
