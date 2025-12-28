<?php

namespace App\Http\Controllers\API;

use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteCartRequest;
use App\Http\Requests\StoreCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartController extends Controller {

  public function index() {
    $user = Auth::user();
    return Cache::tags(['cartItems'])->remember("cartItems.{$user->id}", now()->add('hour', 1), function() use ($user) {
      $cart = Cart::with([
        'items.product:id,name,slug',
        'items.productVariation.variationOptions.variationOption'
      ])->whereUserId($user->id)->first();

      $cartItems = [];

      if (!$cart) {
        return $cartItems;
      }

      foreach($cart->items as $cartItem) {
        // if the cart item no longer exist, delete
        if (!$cartItem->productVariation) {
          $cartItem->delete();
          continue;
        }

        $options = [];
        foreach($cartItem->productVariation->variationOptions as $variationOption) {
          $options[] = $variationOption->variationOption->value;
        }

        $cartItems[] = [
          'cart_item_id' => $cartItem->id,
          'product_name' => $cartItem->product->name,
          'product_image' => $cartItem->product->images->count() ? $cartItem->product->images[0] : null,
          'product_variation_id' => $cartItem->product_variation_id,
          'product_url' => route('products.details', ['productSlug' => $cartItem->product->slug]),
          'product_stock' => $cartItem->productVariation->stock,
          'quantity' => $cartItem->quantity,
          'price' => $cartItem->price_at_time,
          'price_discount' => $cartItem->price_discount_at_time,
          'subtotal' => $cartItem->subtotal,
          'options' => $options,
        ];
      }

      return $cartItems;
    });
  }

  public function store(StoreCartRequest $request) {
    $user = Auth::user();
    $cart = $user->cart;

    // fallback create cart if not exist during login
    if (!$cart) {
      $cart = Cart::firstOrCreate(
        ['user_id' => $user->id]
      );
    }

    if (!app()->environment('production')) {
      Log::channel('cart')->info('atc', array_merge(request()->all(), [
        'user_id' => $user->id
      ]));
    }

    $productVariation = ProductVariation::find($request->product_variation_id);

    $cartItem = CartItem::where([
      'cart_id' => $cart->id,
      'product_id' => $request->product_id,
      'product_variation_id' => $request->product_variation_id,
    ])->first();

    $response = response([
      'success' => false,
      'message' => 'Unexpected Error'
    ], 500);

    DB::transaction(function() use ($request, &$response, $cart, $productVariation, $cartItem) {
      if (!$cartItem) {
        if ($request->quantity > $productVariation->stock) {
          $response = response([
            'success' => false,
            'message' => 'Stok tidak mencukupi'
          ], 400);
          return;
        }

        CartItem::create([
          'cart_id' => $cart->id,
          'product_id' => $request->product_id,
          'product_variation_id' => $request->product_variation_id,
          'quantity' => $request->quantity,
          'price_at_time' => $productVariation->price,
          'price_discount_at_time' => $productVariation->discount_price,
        ]);
      } else {
        if ($cartItem->quantity + $request->quantity > $productVariation->stock) {
          $response = response([
            'success' => false,
            'message' => 'Stok tidak mencukupi'
          ], 400);
          return;
        }

        $cartItem->increment('quantity', $request->quantity);
      }

      $response = response([
        'success' => true,
        'message' => 'Barang telah ditambahkan ke keranjang'
      ]);
    });

    return $response;
  }

  public function update(CartItem $cartItem, UpdateCartRequest $request) {
    // $cartItem->product_variation_id = $request->product_variation_id;
    $cartItem->quantity = $request->quantity;
    $cartItem->save();

    return response([]);
  }

  public function destroy(CartItem $cartItem, DeleteCartRequest $request) {
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
