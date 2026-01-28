<?php

namespace App\Http\Controllers\API;

use App\Helpers\Komerce;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CheckoutSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShippingController extends Controller {

  public function calculate() {
    $request = request()->validate([
      'postal_code' => 'required|digits:5',
    ]);

    $cart = Cart::calculateWeightAndValue(Auth::id());

    if (!$cart['weight_in_kg']) {
      return response(['message' => '[err] keranjang memiliki beban 0'], 400);
    }

    $result = Komerce::calculateByPostalCode($request['postal_code'], $cart['weight_in_kg'], $cart['package_value']);
    if (empty($result)) {
      return response(['message' => 'destination not found'], 404);
    }

    return $result;
  }

  public function calculateForSessionCheckout(Request $request) {
    $request->validate([
      'postal_code' => 'required|digits:5',
    ]);

    $session = $request->checkoutSession;

    $cart = CheckoutSession::calculateWeightAndValue($session->session_id);

    if (!$cart['weight_in_kg']) {
      return response(['message' => '[err] keranjang memiliki beban 0'], 400);
    }

    $result = Komerce::calculateByPostalCode($request['postal_code'], $cart['weight_in_kg'], $cart['package_value']);
    if (isset($result['meta']) && empty($result['data'])) {
      return response($result, $result['meta']['code']);
    }

    return $result;
  }

}
