<?php

namespace App\Http\Controllers\API;

use App\Helpers\DataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUpdateReviewStatusRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminReviewController extends Controller
{
    public function page()
    {
        return view('admin.review.index');
    }

    public function index(Request $request)
    {
        if ($request->view == 'datatable') {
            $query = Review::with(['user:id,name,email', 'product:id,name,slug', 'images'])
                ->whereNull('deleted_at');

            $status = $request->input('status');
            $rating = $request->input('rating');
            $search = $request->input('search');
            $sortBy = $request->input('sort_by', 'latest');

            if ($status) {
                $query->where('status', $status);
            }

            if ($rating) {
                $query->where('rating', $rating);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%");
                    })->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                });
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
                    $query->orderByDesc('created_at');
                    break;
            }

            return DataTable::ajaxTable($query);
        }

        $query = Review::with(['user:id,name,email', 'product:id,name,slug', 'images'])
            ->whereNull('deleted_at');

        $status = $request->input('status');
        $rating = $request->input('rating');
        $sortBy = $request->input('sort_by', 'latest');

        if ($status) {
            $query->where('status', $status);
        }

        if ($rating) {
            $query->where('rating', $rating);
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
