<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartController extends Controller {

  public function index() {
    $sessionId = request()->cookie('session_id');
    return Cache::remember("cartItems.{$sessionId}", now()->add('hour', 1), function()use ($sessionId) {
      $cart = Cart::with([
        'items.product:id,name',
        'items.productVariation.variationOptions.variationOption'
      ])->whereSessionId($sessionId)->first();

      $cartItems = [];

      if (!$cart) {
        return $cartItems;
      }

      foreach($cart->items as $cartItem) {
        $options = [];
        foreach($cartItem->productVariation->variationOptions as $variationOption) {
          $options[] = $variationOption->variationOption->value;
        }

        $cartItems[] = [
          'cart_item_id' => $cartItem->id,
          'product_name' => $cartItem->product->name,
          'product_image' => $cartItem->product->images->count() ? $cartItem->product->images[0] : null,
          'product_variation_id' => $cartItem->product_variation_id,
          'quantity' => $cartItem->quantity,
          'price' => $cartItem->price_at_time,
          'price_discount' => $cartItem->price_discount_at_time,
          'options' => $options,
        ];
      }

      return $cartItems;
    });
  }

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

      $cartItem = CartItem::firstOrNew([
        'cart_id' => $cart->id,
        'product_id' => $request->product_id,
        'product_variation_id' => $request->product_variation_id,
      ]);

      $cartItem->price_at_time = $productVariation->price;
      $cartItem->price_discount_at_time = $productVariation->discount_price;
      $cartItem->quantity += 1;
      $cartItem->save();

      $response = response([
        'success' => true,
        'message' => 'Barang telah ditambahkan ke keranjang'
      ]);
    });

    return $response;
  }

  public function destroy(CartItem $cartItem) {
    $cartItem->delete();

    return response([]);
  }

  public function count() {
    $cart = Cart::withCount(['items'])->first();
    if (!$cart) {
      return response([
        'items_count' => 0,
      ]);
    }

    return $cart;
  }

}
