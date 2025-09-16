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

    $destination = Komerce::searchDestination($request['postal_code']);
    if (!isset($destination['data']) || (isset($destination['data']) && !count($destination['data']))) {
      return response([
        'message' => 'destination not found'
      ], 404);
    }

    $shippingOptions = Komerce::calculate($destination['data'][0]['id']);

    // e.g: reguler, cargo, instant
    $shippingTypes = array_keys($shippingOptions['data']);

    // remap because there are informations user shouldnt know about
    $remapShippingOptions = [];
    foreach($shippingTypes as $shippingType) {
      foreach($shippingOptions['data'][$shippingType] as $option) {
        $remapShippingOptions[] = [
          'shipping_name' => $option['shipping_name'],
          'service_name' => $option['service_name'],
          'shipping_cost' => $option['shipping_cost'],
          'etd' => $option['etd'],
        ];
      }
    }

    return $remapShippingOptions;
  }

}
