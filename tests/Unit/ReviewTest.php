<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function test_review_belongs_to_user()
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'title' => 'Great product',
            'content' => 'Really loved it!',
            'created_by' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $review->user);
        $this->assertEquals($user->id, $review->user->id);
    }

    public function test_review_belongs_to_product()
    {
        $product = $this->createProduct();
        $user = User::factory()->create();
        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 4,
            'created_by' => $user->id,
        ]);

        $this->assertInstanceOf(Product::class, $review->product);
        $this->assertEquals($product->id, $review->product->id);
    }

    public function test_review_belongs_to_order()
    {
        $product = $this->createProduct();
        $user = User::factory()->create();
        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => 1,
            'rating' => 5,
            'is_verified_purchase' => true,
            'created_by' => $user->id,
        ]);

        $this->assertEquals(1, $review->order_id);
        $this->assertTrue($review->is_verified_purchase);
    }

    public function test_review_has_many_images()
    {
        $product = $this->createProduct();
        $user = User::factory()->create();
        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 4,
            'created_by' => $user->id,
        ]);

        ReviewImage::create([
            'review_id' => $review->id,
            'image_path' => 'reviews/image1.jpg',
            'sort_order' => 0,
        ]);

        ReviewImage::create([
            'review_id' => $review->id,
            'image_path' => 'reviews/image2.jpg',
            'sort_order' => 1,
        ]);

        $this->assertEquals(2, $review->images->count());
        $this->assertEquals('reviews/image1.jpg', $review->images->first()->image_path);
    }

    public function test_product_review_stats_update_after_review_created()
    {
        $product = $this->createProduct();
        $user = User::factory()->create();

        $this->assertEquals(0, $product->review_count);
        $this->assertEquals(0, $product->review_avg);

        Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'created_by' => $user->id,
        ]);

        $product->updateReviewStats();
        $product->refresh();

        $this->assertEquals(1, $product->review_count);
        $this->assertEquals(5.0, $product->review_avg);

        $user2 = User::factory()->create();
        Review::create([
            'user_id' => $user2->id,
            'product_id' => $product->id,
            'rating' => 3,
            'created_by' => $user2->id,
        ]);

        $product->updateReviewStats();
        $product->refresh();

        $this->assertEquals(2, $product->review_count);
        $this->assertEquals(4.0, $product->review_avg);
    }

    public function test_product_review_stats_update_after_review_deleted()
    {
        $product = $this->createProduct();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Review::create([
            'user_id' => $user1->id,
            'product_id' => $product->id,
            'rating' => 5,
            'created_by' => $user1->id,
        ]);

        $review2 = Review::create([
            'user_id' => $user2->id,
            'product_id' => $product->id,
            'rating' => 3,
            'created_by' => $user2->id,
        ]);

        $product->updateReviewStats();
        $product->refresh();

        $this->assertEquals(2, $product->review_count);
        $this->assertEquals(4.0, $product->review_avg);

        $review2->delete();
        $product->updateReviewStats();
        $product->refresh();

        $this->assertEquals(1, $product->review_count);
        $this->assertEquals(5.0, $product->review_avg);
    }

    public function test_product_review_stats_update_after_soft_deleted_review_restored()
    {
        $product = $this->createProduct();
        $user = User::factory()->create();

        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 4,
            'created_by' => $user->id,
        ]);

        $product->updateReviewStats();
        $product->refresh();

        $this->assertEquals(1, $product->review_count);

        $review->delete();
        $product->updateReviewStats();
        $product->refresh();

        $this->assertEquals(0, $product->review_count);

        $review->restore();
        $product->updateReviewStats();
        $product->refresh();

        $this->assertEquals(1, $product->review_count);
    }

    public function test_review_with_title_and_content()
    {
        $product = $this->createProduct();
        $user = User::factory()->create();
        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 4,
            'title' => 'Good value for money',
            'content' => 'The product quality is great and shipping was fast. Highly recommended!',
            'created_by' => $user->id,
        ]);

        $this->assertEquals('Good value for money', $review->title);
        $this->assertEquals('The product quality is great and shipping was fast. Highly recommended!', $review->content);
        $this->assertEquals(4, $review->rating);
    }

    public function test_review_rating_constraints()
    {
        $product = $this->createProduct();
        $user = User::factory()->create();

        $review1 = Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 1,
            'created_by' => $user->id,
        ]);

        $this->assertEquals(1, $review1->rating);

        $review2 = Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'created_by' => $user->id,
        ]);

        $product->updateReviewStats();
        $product->refresh();

        $this->assertEquals(3.0, $product->review_avg);
    }

    public function test_review_with_multiple_images_sorted_by_sort_order()
    {
        $product = $this->createProduct();
        $user = User::factory()->create();
        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'created_by' => $user->id,
        ]);

        ReviewImage::create([
            'review_id' => $review->id,
            'image_path' => 'reviews/third.jpg',
            'sort_order' => 2,
        ]);

        ReviewImage::create([
            'review_id' => $review->id,
            'image_path' => 'reviews/first.jpg',
            'sort_order' => 0,
        ]);

        ReviewImage::create([
            'review_id' => $review->id,
            'image_path' => 'reviews/second.jpg',
            'sort_order' => 1,
        ]);

        $images = $review->images;
        $this->assertEquals('reviews/first.jpg', $images[0]->image_path);
        $this->assertEquals('reviews/second.jpg', $images[1]->image_path);
        $this->assertEquals('reviews/third.jpg', $images[2]->image_path);
    }

    public function test_review_images_cascade_on_delete()
    {
        $product = $this->createProduct();
        $user = User::factory()->create();
        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'created_by' => $user->id,
        ]);

        $reviewId = $review->id;

        $image1 = ReviewImage::create([
            'review_id' => $reviewId,
            'image_path' => 'reviews/image1.jpg',
        ]);

        $image2 = ReviewImage::create([
            'review_id' => $reviewId,
            'image_path' => 'reviews/image2.jpg',
        ]);

        $this->assertEquals(2, ReviewImage::count());

        $review->delete();

        $this->assertSoftDeleted('reviews', ['id' => $reviewId]);

        $this->assertDatabaseHas('review_images', [
            'id' => $image1->id,
            'review_id' => $reviewId,
        ]);

        $this->assertDatabaseHas('review_images', [
            'id' => $image2->id,
            'review_id' => $reviewId,
        ]);
    }

    public function test_review_with_null_order_id()
    {
        $product = $this->createProduct();
        $user = User::factory()->create();
        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => null,
            'rating' => 3,
            'is_verified_purchase' => false,
            'created_by' => $user->id,
        ]);

        $this->assertNull($review->order_id);
        $this->assertFalse($review->is_verified_purchase);
    }

    public function test_update_review_stats_calculates_correct_average()
    {
        $product = $this->createProduct();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        $user4 = User::factory()->create();

        Review::create([
            'user_id' => $user1->id,
            'product_id' => $product->id,
            'rating' => 5,
            'created_by' => $user1->id,
        ]);

        Review::create([
            'user_id' => $user2->id,
            'product_id' => $product->id,
            'rating' => 4,
            'created_by' => $user2->id,
        ]);

        Review::create([
            'user_id' => $user3->id,
            'product_id' => $product->id,
            'rating' => 3,
            'created_by' => $user3->id,
        ]);

        Review::create([
            'user_id' => $user4->id,
            'product_id' => $product->id,
            'rating' => 2,
            'created_by' => $user4->id,
        ]);

        $product->updateReviewStats();
        $product->refresh();

        $this->assertEquals(4, $product->review_count);
        $this->assertEquals(3.5, $product->review_avg);
    }

    public function test_update_review_stats_with_no_reviews()
    {
        $product = $this->createProduct();

        $product->updateReviewStats();
        $product->refresh();

        $this->assertEquals(0, $product->review_count);
        $this->assertEquals(0, $product->review_avg);
    }

    protected function createProduct(): Product
    {
        $user = User::factory()->create();
        return Product::create([
            'store_id' => 1,
            'name' => 'Test Product',
            'slug' => 'test-product-'.uniqid(),
            'review_avg' => 0,
            'review_count' => 0,
            'sold_count' => 0,
            'created_by' => $user->id,
            'source' => 'test',
        ]);
    }
}
