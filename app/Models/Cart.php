<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Cart extends Model {

  protected $fillable = [
    'user_id',
    'session_id'
  ];

  protected $hidden = [
    'id',
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

    $cart = self::whereUserId($userId)->first();
    if ($cart) {
      foreach($cart->items as $cartItem) {
        $totalWeightInGrams += ($cartItem->productVariation->weight ?? 500) * $cartItem->quantity;
        $totalItemValue += ($cartItem->productVariation->discount_price ?? $cartItem->productVariation->price) * $cartItem->quantity;
      }
    }

    $totalWeightInKg = $totalWeightInGrams / 1000;

    return [
      'weight_in_kg' => $totalWeightInKg,
      'package_value' => $totalItemValue
    ];
  }

}
