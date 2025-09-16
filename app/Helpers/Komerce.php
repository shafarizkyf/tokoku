<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class Komerce {

  public $komerce;

  function __construct() {
    $this->komerce = Http::baseUrl(env('KOMERCE_URL', 'https://api-sandbox.collaborator.komerce.id'))
      ->withHeader('x-api-key', env('KOMERCE_SHIPPING_DELIVERY_KEY'));
  }

  public static function searchDestination(string $keyword) {
    $instance = new self();
    $response = $instance->komerce->get('tariff/api/v1/destination/search', [
      'keyword' => $keyword,
    ]);

    return $response->json();
  }

  public static function calculate(string | int $destinationId, int $weight = 1, int $itemValue = 10000) {
    $instance = new self();
    $response = $instance->komerce->get('tariff/api/v1/calculate', [
      'shipper_destination_id' => env('KOMERCE_SHIPPER_DESTINATION_ID'),
      'receiver_destination_id' => $destinationId,
      'weight' => $weight,
      'item_value' => $itemValue
    ]);

    return $response->json();
  }

}