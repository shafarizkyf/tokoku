<?php

namespace App\Http\Controllers\API;

use App\Helpers\Komerce;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ShippingController extends Controller {

  public function calculate() {
    $request = request()->validate([
      'postal_code' => 'required|digits:5',
    ]);

    $result = Komerce::calculateByPostalCode($request['postal_code']);
    if (empty($result)) {
      return response(['message' => 'destination not found'], 404);
    }

    return $result;
  }

}
