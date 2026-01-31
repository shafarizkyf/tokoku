<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'image_path',
        'sort_order',
    ];

    public $timestamps = false;

    protected function casts()
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function review()
    {
        return $this->belongsTo(Review::class);
    }
}
