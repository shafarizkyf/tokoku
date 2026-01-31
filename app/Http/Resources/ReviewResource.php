<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'title' => $this->title,
            'content' => $this->content,
            'is_verified_purchase' => $this->is_verified_purchase,
            'status' => $this->status,
            'created_at' => $this->created_at->toIso8601String(),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'path' => asset('storage/'.$image->image_path),
                        'sort_order' => $image->sort_order,
                    ];
                });
            }),
        ];
    }
}
