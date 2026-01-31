<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUpdateReviewStatusRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['user:id,name,email', 'product:id,name,slug', 'images'])
            ->whereNull('deleted_at');

        $status = $request->input('status');
        $rating = $request->input('rating');
        $productId = $request->input('product_id');
        $userId = $request->input('user_id');
        $sortBy = $request->input('sort_by', 'latest');

        if ($status) {
            $query->where('status', $status);
        }

        if ($rating) {
            $query->where('rating', $rating);
        }

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($userId) {
            $query->where('user_id', $userId);
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

    public function updateStatus(Review $review, AdminUpdateReviewStatusRequest $request)
    {
        DB::transaction(function () use ($review, $request) {
            $review->update([
                'status' => $request->status,
                'updated_by' => Auth::id(),
            ]);

            $review->product->updateReviewStats();
        });

        $review->load(['user:id,name', 'product:id,name', 'images']);

        return response()->json([
            'success' => true,
            'message' => 'Status review berhasil diperbarui',
            'data' => new ReviewResource($review),
        ]);
    }

    public function destroy(Review $review)
    {
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
}
