<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReviewImageController extends Controller
{
    public function store(Review $review, Request $request)
    {
        $user = Auth::user();

        if ($review->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat menambahkan gambar pada review ini',
            ], 403);
        }

        $existingCount = ReviewImage::where('review_id', $review->id)->count();
        if ($existingCount >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Maksimal 5 gambar per review',
            ], 400);
        }

        $request->validate([
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $images = [];
        $sortOrder = $existingCount;

        DB::transaction(function () use ($review, $request, &$images, &$sortOrder) {
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    if ($sortOrder >= 5) {
                        break;
                    }

                    $filename = uniqid().'.'.$file->getClientOriginalExtension();
                    $path = $file->storeAs("reviews/{$review->id}", $filename, 'public');

                    $reviewImage = ReviewImage::create([
                        'review_id' => $review->id,
                        'image_path' => $path,
                        'sort_order' => $sortOrder,
                        'created_at' => now(),
                    ]);

                    $images[] = [
                        'id' => $reviewImage->id,
                        'path' => $reviewImage->image_path,
                    ];

                    $sortOrder++;
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Gambar berhasil diupload',
            'data' => [
                'images' => $images,
            ],
        ], 201);
    }

    public function destroy(Review $review, ReviewImage $image)
    {
        $user = Auth::user();

        if ($review->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat menghapus gambar ini',
            ], 403);
        }

        if ($image->review_id !== $review->id) {
            return response()->json([
                'success' => false,
                'message' => 'Gambar tidak ditemukan pada review ini',
            ], 404);
        }

        DB::transaction(function () use ($image) {
            Storage::disk('public')->delete($image->image_path);
            $image->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Gambar berhasil dihapus',
        ]);
    }
}
