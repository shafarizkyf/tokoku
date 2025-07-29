<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CartItems extends Model {

  protected $hidden = [
    'cart_id',
    'created_at',
    'updated_at'
  ];

  protected function casts() {
    return [
      'price_at_time' => 'double'
    ];
  }

  protected static function booted(): void {
    static::created(function(){
      Cache::forget('cartItems');
    });

    static::saved(function(){
      Cache::forget('cartItems');
    });

    static::updated(function(){
      Cache::forget('cartItems');
    });

    static::deleted(function(){
      Cache::forget('cartItems');
    });
  }

  public function product() {
    return $this->belongsTo(Product::class);
  }

  public function productVariation() {
    return $this->belongsTo(ProductVariation::class);
  }

}
