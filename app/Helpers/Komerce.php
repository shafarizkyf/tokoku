<?php

namespace App\Helpers;

use App\Models\Cart;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

  public static function calculate(string | int $destinationId, float $weight = 1, int $itemValue = 10000) {
    $instance = new self();
    $response = $instance->komerce->get('tariff/api/v1/calculate', [
      'shipper_destination_id' => env('KOMERCE_SHIPPER_DESTINATION_ID'),
      'receiver_destination_id' => $destinationId,
      'weight' => $weight,
      'item_value' => $itemValue
    ]);

    if (!$response->successful()) {
      Log::channel('komerce')->error('calculate: ' . $response->body());
    }

    return $response->json();
  }

  public static function calculateByPostalCode(string $postalCode, float $weight, int $itemValue): array {
    if (Utils::hasTestHeaderKey()) {
      Log::info('Komerce::calculateByPostalCode - returning mock data for postal code ' . $postalCode);
      return json_decode(file_get_contents(storage_path('mock/komerce_calculate_by_postal_code.json')), true);
    }

    $destination = self::searchDestination($postalCode);
    if (!isset($destination['data']) || (isset($destination['data']) && !count($destination['data']))) {
      return [];
    }

    $shippingOptions = self::calculate($destination['data'][0]['id'], $weight, $itemValue);

    if (!isset($shippingOptions['data'])) {
      return [];
    }

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