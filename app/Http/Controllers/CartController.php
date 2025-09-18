<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller {

  public function index() {
    $sessionId = request()->cookie('session_id');
    $cart = Cart::whereSessionId($sessionId)->first();

    if (!$cart || ($cart && !$cart->items->count())) {
      return redirect()->back()->with('message', 'Keranjang anda kosong. Silahkan berbelanja terlebih dahulu');
    }

    return view('homepage.cart.index');
  }

}
