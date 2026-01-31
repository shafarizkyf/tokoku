<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\Review;

class ReviewObserver
{
    public function saved(Review $review): void
    {
        $this->updateProductReviewStats($review->product_id);
    }

    public function deleted(Review $review): void
    {
        $this->updateProductReviewStats($review->product_id);
    }

    public function restored(Review $review): void
    {
        $this->updateProductReviewStats($review->product_id);
    }

    protected function updateProductReviewStats(int $productId): void
    {
        $product = Product::withoutGlobalScope(\App\Models\Scopes\ProductActive::class)->find($productId);

        if ($product) {
            $product->updateReviewStats();
        }
    }
}
