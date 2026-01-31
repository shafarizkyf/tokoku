<?php

namespace App\Models;

use App\Models\Scopes\ProductActive;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'rating',
        'title',
        'content',
        'is_verified_purchase',
        'status',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::saved(function (Review $review) {
            Cache::tags(['products'])->flush();
        });

        static::deleted(function (Review $review) {
            Cache::tags(['products'])->flush();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)
            ->withoutGlobalScope(ProductActive::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function images()
    {
        return $this->hasMany(ReviewImage::class)->orderBy('sort_order');
    }
}
