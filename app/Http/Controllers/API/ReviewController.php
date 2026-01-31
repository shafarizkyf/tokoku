<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReviewController extends Controller
{
    public function index(Product $product, Request $request)
    {
        $query = Review::where('product_id', $product->id)
            ->with(['user:id,name', 'images'])
            ->whereNull('deleted_at');

        $rating = $request->input('rating');
        $verified = $request->input('verified');
        $sortBy = $request->input('sort_by', 'latest');

        if ($rating) {
            $query->where('rating', $rating);
        }

        if ($verified !== null) {
            $query->where('is_verified_purchase', filter_var($verified, FILTER_VALIDATE_BOOLEAN));
        }

        switch ($sortBy) {
            case 'oldest':
                $query->oldest();
                break;
            case 'highest_rating':
                $query->orderBy('rating', 'desc');
                break;
            case 'lowest_rating':
                $query->orderBy('rating', 'asc');
                break;
            case 'latest':
            default:
                $query->latest();
                break;
        }

        $reviews = $query->paginate(15);

        return ReviewResource::collection($reviews);
    }

    public function summary(Product $product)
    {
        $stats = Review::where('product_id', $product->id)
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) as total_reviews, AVG(rating) as average_rating')
            ->first();

        $distribution = Review::where('product_id', $product->id)
            ->whereNull('deleted_at')
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        $ratingCounts = [];
        for ($i = 1; $i <= 5; $i++) {
            $ratingCounts[$i] = $distribution[$i] ?? 0;
        }

        $verifiedCount = Review::where('product_id', $product->id)
            ->whereNull('deleted_at')
            ->where('is_verified_purchase', true)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_reviews' => $stats->total_reviews ?? 0,
                'average_rating' => round($stats->average_rating ?? 0, 2),
                'rating_distribution' => $ratingCounts,
                'verified_purchase_count' => $verifiedCount,
            ],
        ]);
    }

    public function show(Review $review)
    {
        $review->load(['user:id,name', 'images']);

        return response()->json([
            'success' => true,
            'data' => new ReviewResource($review),
        ]);
    }

    public function store(Product $product, StoreReviewRequest $request)
    {
        $user = Auth::user();

        $existingReview = Review::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->whereNull('deleted_at')
            ->first();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah memberikan review untuk produk ini',
            ], 400);
        }

        $isVerifiedPurchase = false;
        if ($request->has('order_id') && $request->order_id) {
            $isVerifiedPurchase = DB::table('orders')
                ->where('id', $request->order_id)
                ->where('user_id', $user->id)
                ->where('status', 'completed')
                ->exists();
        }

        $review = DB::transaction(function () use ($product, $user, $request, $isVerifiedPurchase) {
            $review = Review::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'order_id' => $request->input('order_id'),
                'rating' => $request->rating,
                'title' => $request->input('title'),
                'content' => $request->input('content'),
                'is_verified_purchase' => $isVerifiedPurchase,
                'created_by' => $user->id,
            ]);

            // Save review images
            if ($request->hasFile('images')) {
                $sortOrder = 0;
                foreach ($request->file('images') as $image) {
                    if ($sortOrder >= 5) {
                        break;
                    }

                    $filename = Str::uuid().'.'.$image->getClientOriginalExtension();
                    $path = $image->storeAs("reviews/{$review->id}", $filename, 'public');

                    ReviewImage::create([
                        'review_id' => $review->id,
                        'image_path' => $path,
                        'sort_order' => $sortOrder,
                        'created_at' => now(),
                    ]);

                    $sortOrder++;
                }
            }

            $product->updateReviewStats();

            return $review;
        });

        $review->load(['user:id,name', 'images']);

        return response()->json([
            'success' => true,
            'message' => 'Review berhasil dibuat',
            'data' => new ReviewResource($review),
        ], 201);
    }

    public function update(Review $review, UpdateReviewRequest $request)
    {
        $user = Auth::user();

        if ($review->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat mengedit review ini',
            ], 403);
        }

        DB::transaction(function () use ($review, $request) {
            $review->update([
                'rating' => $request->rating,
                'title' => $request->input('title'),
                'content' => $request->input('content'),
                'updated_by' => $user->id ?? Auth::id(),
            ]);

            $review->product->updateReviewStats();
        });

        $review->load(['user:id,name', 'images']);

        return response()->json([
            'success' => true,
            'message' => 'Review berhasil diperbarui',
            'data' => new ReviewResource($review),
        ]);
    }

    public function destroy(Review $review)
    {
        $user = Auth::user();

        if ($review->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat menghapus review ini',
            ], 403);
        }

        DB::transaction(function () use ($review) {
            $review->update(['deleted_by' => Auth::id()]);
            $review->delete();

            $review->product->updateReviewStats();
        });

        return response()->json([
            'success' => true,
            'message' => 'Review berhasil dihapus',
        ]);
    }

    public function myReviews(Request $request)
    {
        $user = Auth::user();

        $query = Review::where('user_id', $user->id)
            ->with(['product:id,name,slug,review_avg', 'images'])
            ->whereNull('deleted_at');

        $sortBy = $request->input('sort_by', 'latest');

        switch ($sortBy) {
            case 'oldest':
                $query->oldest();
                break;
            case 'highest_rating':
                $query->orderBy('rating', 'desc');
                break;
            case 'lowest_rating':
                $query->orderBy('rating', 'asc');
                break;
            case 'latest':
            default:
                $query->latest();
                break;
        }

        $reviews = $query->paginate(15);

        return ReviewResource::collection($reviews);
    }
}
