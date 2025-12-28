<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ProductVariationTest extends TestCase
{
    use RefreshDatabase;

    public function test_discount_percentage_accessor_returns_expected_value()
    {
        $product = Product::factory()->create(['id' => 1]);
        $variation = ProductVariation::create([
            'product_id' => $product->id,
            'price' => 100000,
            'discount_price' => 80000,
            'stock' => 10,
            'sku' => 'SKU-1234',
            'weight' => 500,
        ]);
        $this->assertEquals('20', $variation->discount_percentage);
    }

    public function test_discount_percentage_accessor_returns_null_when_no_discount() {
        $product = Product::factory()->create(['id' => 1]);
        $variation = ProductVariation::create([
            'product_id' => $product->id,
            'price' => 100000,
            'discount_price' => null,
            'stock' => 10,
            'sku' => 'SKU-1235',
            'weight' => 500,
        ]);
        $this->assertNull($variation->discount_percentage);
        $this->assertNull($variation->discount_percentage);
    }

    public function test_weight_in_kg_accessor_returns_expected_value()
    {
        $product = Product::factory()->create(['id' => 1]);
        $variation = ProductVariation::create([
            'product_id' => $product->id,
            'price' => 100000,
            'discount_price' => 90000,
            'stock' => 10,
            'sku' => 'SKU-1236',
            'weight' => 1500,
        ]);
        $this->assertEquals('1.50', $variation->weight_in_kg);
    }

    public function test_weight_in_kg_accessor_returns_zero_when_weight_null()
    {
        $product = Product::factory()->create(['id' => 1]);
        $variation = ProductVariation::create([
            'product_id' => $product->id,
            'price' => 100000,
            'discount_price' => 90000,
            'stock' => 10,
            'sku' => 'SKU-1237',
            'weight' => null,
        ]);

        $this->assertEquals(0, $variation->weight_in_kg);
    }
}
