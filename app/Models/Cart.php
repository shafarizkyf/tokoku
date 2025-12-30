<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Cart extends Model {

  use HasFactory;

  protected $fillable = [
    'user_id',
    'session_id'
  ];

  protected $hidden = [
    'created_at',
    'updated_at',
  ];

  protected static function booted(): void {
    static::addGlobalScope('user_id', function (Builder $builder) {
      if ($user = Auth::user()) {
        $builder->where('user_id', $user->id);
      }
    });
  }

  public function items() {
    return $this->hasMany(CartItem::class);
  }

  public function user() {
    return $this->belongsTo(User::class);
  }

  public static function calculateWeightAndValue($userId) {
    $totalWeightInGrams = 0;
    $totalItemValue = 0;

    $cart = self::whereUserId($userId)->get();
    if (!count($cart)) {
        return [
            'weight_in_kg' => 0,
            'package_value' => 0
        ];
    }

    $cartItems = CartItem::whereIn('cart_id', $cart->pluck('id'))
      ->with('productVariation')
      ->get();

    foreach($cartItems as $cartItem) {
      $variation = $cartItem->productVariation;
      if ($variation) {
        $weight = $variation->weight;
        $price = $variation->discount_price ?? $variation->price;
      } else {
        // Fallback values if ProductVariation is missing
        $weight = 500;
        $price = $cartItem->price_discount_at_time ?? $cartItem->price_at_time;
      }

      $totalWeightInGrams += $weight * $cartItem->quantity;
      $totalItemValue += $price * $cartItem->quantity;
    }

    $totalWeightInKg = $totalWeightInGrams / 1000;

    return [
      'weight_in_kg' => $totalWeightInKg,
      'package_value' => $totalItemValue
    ];
  }

}
