<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Review;
use App\Models\ReviewImage;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private $admin;

    private $user;

    private $user2;

    private $shop;

    private $product;

    private $order;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::create([
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'provider' => 'google',
        ]);

        $this->user = User::create([
            'id' => 2,
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'provider' => 'google',
        ]);

        $this->user2 = User::create([
            'id' => 3,
            'name' => 'Another User',
            'email' => 'another@example.com',
            'password' => bcrypt('password'),
            'provider' => 'google',
        ]);

        $this->shop = Shop::create([
            'id' => 1,
            'name' => 'Test Shop',
            'description' => 'Test Description',
            'image_path' => 'test/path.jpg',
        ]);

        $this->product = Product::create([
            'store_id' => $this->shop->id,
            'created_by' => $this->admin->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'is_active' => true,
            'review_avg' => 0,
            'review_count' => 0,
        ]);

        $variation = ProductVariation::create([
            'product_id' => $this->product->id,
            'sku' => 'TEST-VARIATION-001',
            'price' => 100000,
            'stock' => 10,
            'weight' => 1000,
        ]);

        $this->order = Order::create([
            'user_id' => $this->user->id,
            'code' => 'TEST-ORDER-001',
            'recipient_name' => 'John Doe',
            'recipient_email' => 'john@example.com',
            'recipient_phone' => '081234567890',
            'address_detail' => '123 Test Street',
            'province_id' => 1,
            'regency_id' => 1,
            'district_id' => 1,
            'village_id' => 1,
            'postal_code' => '12345',
            'payment_method' => 'bank_transfer',
            'payment_status' => 'paid',
            'total_price' => 100000,
            'total_weight' => 1000,
            'shipping_price' => 15000,
            'total_amount' => 115000,
            'grand_total' => 115000,
            'courier' => 'jne',
            'status' => 'completed',
        ]);

        OrderDetail::create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'product_variation_id' => $variation->id,
            'name_snapshot' => 'Test Product',
            'variation_snapshot' => 'TEST-VARIATION-001',
            'quantity' => 1,
            'price' => 100000,
            'subtotal' => 100000,
            'weight' => 1000,
        ]);
    }

    public function test_can_get_product_reviews()
    {
        Review::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'order_id' => $this->order->id,
            'rating' => 5,
            'title' => 'Great product',
            'content' => 'I love this product!',
            'is_verified_purchase' => true,
            'status' => 'approved',
            'created_by' => $this->user->id,
        ]);

        Review::create([
            'user_id' => $this->user2->id,
            'product_id' => $this->product->id,
            'rating' => 4,
            'title' => 'Good product',
            'content' => 'Pretty good, would recommend.',
            'is_verified_purchase' => false,
            'status' => 'approved',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->getJson("/api/products/{$this->product->id}/reviews");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user' => ['id', 'name'],
                        'rating',
                        'title',
                        'content',
                        'is_verified_purchase',
                        'created_at',
                        'images',
                    ],
                ],
                'links',
                'meta',
            ]);

        $this->assertCount(2, $response->json()['data']);
    }

    public function test_can_filter_reviews_by_rating()
    {
        Review::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'order_id' => $this->order->id,
            'rating' => 5,
            'title' => 'Five stars',
            'content' => 'Amazing!',
            'is_verified_purchase' => true,
            'status' => 'approved',
            'created_by' => $this->user->id,
        ]);

        Review::create([
            'user_id' => $this->user2->id,
            'product_id' => $this->product->id,
            'rating' => 3,
            'title' => 'Three stars',
            'content' => 'Average.',
            'is_verified_purchase' => false,
            'status' => 'approved',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->getJson("/api/products/{$this->product->id}/reviews?rating=5");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json()['data']);
        $this->assertEquals(5, $response->json()['data'][0]['rating']);
    }

    public function test_can_filter_reviews_by_verified()
    {
        Review::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'order_id' => $this->order->id,
            'rating' => 5,
            'title' => 'Verified review',
            'content' => 'From verified purchase.',
            'is_verified_purchase' => true,
            'status' => 'approved',
            'created_by' => $this->user->id,
        ]);

        Review::create([
            'user_id' => $this->user2->id,
            'product_id' => $this->product->id,
            'rating' => 4,
            'title' => 'Unverified review',
            'content' => 'Not from purchase.',
            'is_verified_purchase' => false,
            'status' => 'approved',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->getJson("/api/products/{$this->product->id}/reviews?verified=true");

        $response->assertStatus(200);
        $data = $response->json()['data'];
        $this->assertNotEmpty($data);
        foreach ($data as $review) {
            $this->assertEquals(1, $review['is_verified_purchase']);
        }
    }

    public function test_can_get_review_summary()
    {
        Review::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'order_id' => $this->order->id,
            'rating' => 5,
            'title' => 'Great',
            'content' => 'Loved it!',
            'is_verified_purchase' => true,
            'status' => 'approved',
            'created_by' => $this->user->id,
        ]);

        Review::create([
            'user_id' => $this->user2->id,
            'product_id' => $this->product->id,
            'rating' => 4,
            'title' => 'Good',
            'content' => 'Nice product.',
            'is_verified_purchase' => false,
            'status' => 'approved',
            'created_by' => $this->admin->id,
        ]);

        Review::create([
            'user_id' => $this->user2->id,
            'product_id' => $this->product->id,
            'rating' => 4,
            'title' => 'Good product',
            'content' => 'Pretty good, would recommend.',
            'is_verified_purchase' => false,
            'status' => 'approved',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->getJson("/api/products/{$this->product->id}/reviews/summary");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_reviews',
                    'average_rating',
                    'rating_distribution' => [1, 2, 3, 4, 5],
                    'verified_purchase_count',
                ],
            ]);

        $this->assertEquals(4.33, round($response->json()['data']['average_rating'], 2));
        $this->assertEquals(3, $response->json()['data']['total_reviews']);
    }

    public function test_can_view_single_review()
    {
        $review = Review::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'order_id' => $this->order->id,
            'rating' => 5,
            'title' => 'My Review',
            'content' => 'This is my detailed review.',
            'is_verified_purchase' => true,
            'status' => 'approved',
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson("/api/reviews/{$review->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $review->id,
                    'rating' => 5,
                    'title' => 'My Review',
                    'content' => 'This is my detailed review.',
                ],
            ]);
    }

    public function test_cannot_view_non_existent_review()
    {
        $response = $this->getJson('/api/reviews/99999');

        $response->assertStatus(404);
    }

    public function test_authenticated_user_can_create_review()
    {
        Sanctum::actingAs($this->user, ['user']);

        $payload = [
            'order_id' => $this->order->id,
            'rating' => 5,
            'title' => 'Excellent product',
            'content' => 'This product exceeded my expectations.',
        ];

        $response = $this->postJson("/api/products/{$this->product->id}/reviews", $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'rating',
                    'title',
                    'content',
                ],
            ]);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'rating' => 5,
            'title' => 'Excellent product',
            'is_verified_purchase' => 1,
            'status' => 'pending',
        ]);

        $product = Product::find($this->product->id);
        $this->assertEquals(5, $product->review_avg);
        $this->assertEquals(1, $product->review_count);
    }

    public function test_unauthenticated_user_cannot_create_review()
    {
        $payload = [
            'rating' => 5,
            'title' => 'Great',
            'content' => 'Love it!',
        ];

        $response = $this->postJson("/api/products/{$this->product->id}/reviews", $payload);

        $response->assertStatus(401);
    }

    public function test_cannot_create_review_without_required_fields()
    {
        Sanctum::actingAs($this->user, ['user']);

        $response = $this->postJson("/api/products/{$this->product->id}/reviews", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_cannot_create_review_with_invalid_rating()
    {
        Sanctum::actingAs($this->user, ['user']);

        $payload = [
            'rating' => 6,
            'title' => 'Invalid rating',
            'content' => 'This should fail.',
        ];

        $response = $this->postJson("/api/products/{$this->product->id}/reviews", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_user_can_update_own_review()
    {
        Sanctum::actingAs($this->user, ['user']);

        $review = Review::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'order_id' => $this->order->id,
            'rating' => 4,
            'title' => 'Original Title',
            'content' => 'Original content.',
            'is_verified_purchase' => true,
            'status' => 'approved',
            'created_by' => $this->user->id,
        ]);

        $payload = [
            'rating' => 5,
            'title' => 'Updated Title',
            'content' => 'Updated content with more details.',
        ];

        $response = $this->putJson("/api/reviews/{$review->id}", $payload);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'title' => 'Updated Title',
            'content' => 'Updated content with more details.',
        ]);
    }

    public function test_user_cannot_update_others_review()
    {
        Sanctum::actingAs($this->user, ['user']);

        $review = Review::create([
            'user_id' => $this->user2->id,
            'product_id' => $this->product->id,
            'rating' => 4,
            'title' => 'User2 Review',
            'content' => 'Content.',
            'is_verified_purchase' => false,
            'status' => 'approved',
            'created_by' => $this->admin->id,
        ]);

        $payload = [
            'rating' => 5,
            'title' => 'Hacked Title',
            'content' => 'Hacked content.',
        ];

        $response = $this->putJson("/api/reviews/{$review->id}", $payload);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_review()
    {
        Sanctum::actingAs($this->user, ['user']);

        $review = Review::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'order_id' => $this->order->id,
            'rating' => 4,
            'title' => 'To be deleted',
            'content' => 'Delete me.',
            'is_verified_purchase' => true,
            'status' => 'approved',
            'created_by' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/reviews/{$review->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('reviews', ['id' => $review->id]);

        $product = Product::find($this->product->id);
        $this->assertEquals(0, $product->review_avg);
        $this->assertEquals(0, $product->review_count);
    }

    public function test_user_cannot_delete_others_review()
    {
        Sanctum::actingAs($this->user, ['user']);

        $review = Review::create([
            'user_id' => $this->user2->id,
            'product_id' => $this->product->id,
            'rating' => 4,
            'title' => 'Protected Review',
            'content' => 'Cannot delete.',
            'is_verified_purchase' => false,
            'status' => 'approved',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->deleteJson("/api/reviews/{$review->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_get_own_reviews()
    {
        Sanctum::actingAs($this->user, ['user']);

        Review::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'order_id' => $this->order->id,
            'rating' => 5,
            'title' => 'My Review 1',
            'content' => 'First review.',
            'is_verified_purchase' => true,
            'status' => 'approved',
            'created_by' => $this->user->id,
        ]);

        Review::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'order_id' => $this->order->id,
            'rating' => 4,
            'title' => 'My Review 2',
            'content' => 'Second review.',
            'is_verified_purchase' => true,
            'status' => 'approved',
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/users/me/reviews');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'rating', 'title', 'content', 'is_verified_purchase', 'created_at', 'user', 'images'],
                ],
                'links',
                'meta',
            ]);

        $this->assertCount(2, $response->json()['data']);
    }

    public function test_user_can_upload_review_images()
    {
        Sanctum::actingAs($this->user, ['user']);

        $review = Review::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'order_id' => $this->order->id,
            'rating' => 5,
            'title' => 'With Images',
            'content' => 'Review with photos.',
            'is_verified_purchase' => true,
            'status' => 'approved',
            'created_by' => $this->user->id,
        ]);

        $file = UploadedFile::fake()->image('review.jpg');

        $response = $this->postJson("/api/reviews/{$review->id}/images", [
            'images' => [$file],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Gambar berhasil diupload',
            ]);

        $this->assertDatabaseHas('review_images', [
            'review_id' => $review->id,
        ]);

        $review->refresh();
        $this->assertCount(1, $review->images);
    }

    public function test_user_cannot_upload_more_than_five_images()
    {
        Sanctum::actingAs($this->user, ['user']);

        $review = Review::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'rating' => 5,
            'title' => 'Too Many Images',
            'content' => 'Test.',
            'is_verified_purchase' => true,
            'status' => 'approved',
            'created_by' => $this->user->id,
        ]);

        $files = [
            UploadedFile::fake()->image('1.jpg'),
            UploadedFile::fake()->image('2.jpg'),
            UploadedFile::fake()->image('3.jpg'),
            UploadedFile::fake()->image('4.jpg'),
            UploadedFile::fake()->image('5.jpg'),
            UploadedFile::fake()->image('6.jpg'),
        ];

        $response = $this->postJson("/api/reviews/{$review->id}/images", [
            'images' => $files,
        ]);

        $response->assertStatus(201);

        $review->refresh();
        $this->assertCount(5, $review->images);
    }

    public function test_user_can_delete_review_image()
    {
        Sanctum::actingAs($this->user, ['user']);

        $review = Review::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'order_id' => $this->order->id,
            'rating' => 5,
            'title' => 'Image Delete Test',
            'content' => 'Test.',
            'is_verified_purchase' => true,
            'status' => 'approved',
            'created_by' => $this->user->id,
        ]);

        $image = ReviewImage::create([
            'review_id' => $review->id,
            'image_path' => 'reviews/test/image.jpg',
            'sort_order' => 0,
        ]);

        $response = $this->deleteJson("/api/reviews/{$review->id}/images/{$image->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('review_images', ['id' => $image->id]);
    }

    public function test_admin_can_view_all_reviews()
    {
        Sanctum::actingAs($this->admin, ['admin']);

        Review::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'rating' => 5,
            'title' => 'Review 1',
            'content' => 'First review.',
            'is_verified_purchase' => true,
            'status' => 'pending',
            'created_by' => $this->admin->id,
        ]);

        Review::create([
            'user_id' => $this->user2->id,
            'product_id' => $this->product->id,
            'rating' => 4,
            'title' => 'Review 2',
            'content' => 'Second review.',
            'is_verified_purchase' => false,
            'status' => 'approved',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->getJson('/api/admin/reviews');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user' => ['id', 'name'],
                        'rating',
                        'title',
                        'content',
                        'is_verified_purchase',
                        'created_at',
                        'images',
                    ],
                ],
                'links',
                'meta',
            ]);

        $this->assertCount(2, $response->json()['data']);
    }

    public function test_non_admin_cannot_access_admin_reviews()
    {
        Sanctum::actingAs($this->user, ['user']);

        $response = $this->getJson('/api/admin/reviews');

        $response->assertStatus(403);
    }

    public function test_admin_can_approve_review()
    {
        Sanctum::actingAs($this->admin, ['admin']);

        $review = Review::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'rating' => 5,
            'title' => 'Pending Review',
            'content' => 'Waiting for approval.',
            'is_verified_purchase' => true,
            'status' => 'pending',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->patchJson("/api/admin/reviews/{$review->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertStatus(200);

        $responseData = $response->json();
        $this->assertTrue($responseData['success'] ?? false);

        if (isset($responseData['data'])) {
            $this->assertEquals('approved', $responseData['data']['status']);
        }

        $review->refresh();
        $this->assertEquals('approved', $review->status);
    }

    public function test_admin_can_reject_review()
    {
        Sanctum::actingAs($this->admin, ['admin']);

        $review = Review::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'rating' => 5,
            'title' => 'Spam Review',
            'content' => 'This looks like spam.',
            'is_verified_purchase' => true,
            'status' => 'pending',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->patchJson("/api/admin/reviews/{$review->id}/status", [
            'status' => 'rejected',
        ]);

        $response->assertStatus(200);

        $responseData = $response->json();
        $this->assertTrue($responseData['success'] ?? false);

        if (isset($responseData['data'])) {
            $this->assertEquals('rejected', $responseData['data']['status']);
        }

        $review->refresh();
        $this->assertEquals('rejected', $review->status);
    }

    public function test_admin_can_delete_review()
    {
        Sanctum::actingAs($this->admin, ['admin']);

        $review = Review::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'rating' => 5,
            'title' => 'To be deleted by admin',
            'content' => 'Delete this.',
            'is_verified_purchase' => true,
            'status' => 'approved',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->deleteJson("/api/admin/reviews/{$review->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('reviews', ['id' => $review->id]);

        $product = Product::find($this->product->id);
        $this->assertEquals(0, $product->review_count);
    }
}
