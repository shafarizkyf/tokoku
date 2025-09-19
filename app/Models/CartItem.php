<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CartItem extends Model {

  protected $fillable = [
    'cart_id',
    'product_id',
    'product_variation_id',
    'price_at_time',
    'price_discount_at_time',
  ];

  protected $hidden = [
    'cart_id',
    'created_at',
    'updated_at'
  ];

  public $appends = [
    'subtotal'
  ];

  protected function casts() {
    return [
      'price_at_time' => 'double',
      'price_discount_at_time' => 'double',
    ];
  }

  protected static function booted(): void {
    $sessionId = request()->cookie('session_id');
    $cacheKey = "cartItems.{$sessionId}";

    static::created(function() use ($cacheKey) {
      Cache::forget($cacheKey);
    });

    static::saved(function() use ($cacheKey) {
      Cache::forget($cacheKey);
    });

    static::updated(function() use ($cacheKey) {
      Cache::forget($cacheKey);
    });

    static::deleted(function() use ($cacheKey) {
      Cache::forget($cacheKey);
    });
  }

  public function getSubtotalAttribute() {
    return $this->quantity * ($this->price_discount_at_time ?? $this->price_at_time);
  }

  public function product() {
    return $this->belongsTo(Product::class);
  }

  public function productVariation() {
    return $this->belongsTo(ProductVariation::class);
  }

}
