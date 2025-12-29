<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_with_variations_and_images_returns_expected_data()
    {
        $user = User::factory()->create();
        $product = Product::create([
            'store_id' => 1,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'review_avg' => 4.5,
            'sold_count' => 10,
            'created_by' => $user->id,
            'source' => 'test',
        ]);
        $variation = ProductVariation::create([
            'product_id' => $product->id,
            'price' => 10000,
            'discount_price' => 8000,
            'stock' => 5,
            'weight' => 500,
        ]);
        $image = ProductImage::create([
            'product_id' => $product->id,
            'path' => 'products/test.jpg',
        ]);
        $this->assertEquals('Test Product', $product->name);
        $this->assertEquals($variation->id, $product->variations->first()->id);
        $this->assertEquals($image->id, $product->images->first()->id);
        $this->assertNotNull($product->image);
    }

    public function test_product_with_no_variations_or_images_returns_empty_relations()
    {
        $user = User::factory()->create();
        $product = Product::create([
            'store_id' => 1,
            'name' => 'No Variations',
            'slug' => 'no-variations',
            'review_avg' => 0,
            'sold_count' => 0,
            'created_by' => $user->id,
            'source' => 'test',
        ]);
        $this->assertEmpty($product->variations);
        $this->assertEmpty($product->images);
        $this->assertNull($product->image);
    }

    public function test_product_with_soft_deleted_variation_and_external_image_url()
    {
        $user = User::factory()->create();
        $product = Product::create([
            'store_id' => 1,
            'name' => 'Edge Product',
            'slug' => 'edge-product',
            'review_avg' => 3.0,
            'sold_count' => 1,
            'created_by' => $user->id,
            'source' => 'test',
        ]);
        $variation = ProductVariation::create([
            'product_id' => $product->id,
            'price' => 5000,
            'discount_price' => 4000,
            'stock' => 2,
            'weight' => 250,
        ]);
        $variation->delete(); // Soft delete
        $image = ProductImage::create([
            'product_id' => $product->id,
            'path' => 'http://external.com/image.jpg',
        ]);
        $this->assertSoftDeleted('product_variations', ['id' => $variation->id]);
        $this->assertTrue(str_contains($image->url, 'http://external.com/image.jpg'));
    }
}
