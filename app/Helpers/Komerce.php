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

    public static function calculateByPostalCode(string $postalCode): array {
      $destination = self::searchDestination($postalCode);
      if (!isset($destination['data']) || (isset($destination['data']) && !count($destination['data']))) {
        return [];
      }

      $shippingOptions = self::calculate($destination['data'][0]['id']);

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