<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartRequest;
use App\Models\Cart;
use App\Models\CartItems;
use App\Models\ProductVariation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartController extends Controller {

  public function store(StoreCartRequest $request) {
    Log::channel('cart')->info('atc', array_merge(request()->all(), [
      'session_id' => request()->cookie('session_id')
    ]));

    $response = response([
      'success' => false,
      'message' => 'Unexpected Error'
    ], 500);

    DB::transaction(function() use ($request, &$response) {
      $cart = Cart::updateOrCreate([
        'session_id' => $request->cookie('session_id'),
      ]);

      $productVariation = ProductVariation::find($request->product_variation_id);

      $cartItem = new CartItems;
      $cartItem->cart_id = $cart->id;
      $cartItem->product_id = $request->product_id;
      $cartItem->product_variation_id = $request->product_variation_id;
      $cartItem->price_at_time = $productVariation->price;
      $cartItem->save();

      $response = response([
        'success' => true,
        'message' => 'Saved'
      ]);
    });

    return $response;
  }

  public function count() {
    return Cart::withCount(['items'])
      ->whereSessionId(request()->cookie('session_id'))
      ->first();
  }

}
