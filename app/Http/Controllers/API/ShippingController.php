<?php

namespace App\Http\Controllers\API;

use App\Helpers\Komerce;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShippingController extends Controller {

  public function calculate() {
    $request = request()->validate([
      'postal_code' => 'required|digits:5',
    ]);

    $cart = Cart::calculateWeightAndValue(Auth::id());
    $result = Komerce::calculateByPostalCode($request['postal_code'], $cart['weight_in_kg'], $cart['package_value']);
    if (empty($result)) {
      return response(['message' => 'destination not found'], 404);
    }

    return $result;
  }

}
