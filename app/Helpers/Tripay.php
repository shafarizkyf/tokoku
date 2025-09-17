<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Tripay {

  public $tripay;

  function __construct() {
    $this->tripay = Http::baseUrl(env('TRIPAY_URL', 'https://tripay.co.id/api-sandbox'))
      ->withHeader('Authorization', "Bearer " . env('TRIPAY_MERCHANT_API_KEY'));
  }

  public static function paymentChannels() {
    $instance = new self();
    $response = $instance->tripay->get('/merchant/payment-channel');

    if (!$response->successful()) {
      Log::channel('tripay')->error('paymentChannels: ' . $response->body());
      return [];
    }

    return $response->json();
  }

  private static function signature($merchantRef, $amount) {
    $privateKey = env('TRIPAY_MERCHANT_PRIVATE_KEY');
    $merchantCode = env('TRIPAY_MERCHANT_CODE');
    return hash_hmac('sha256', $merchantCode . $merchantRef . $amount, $privateKey);
  }

  public static function requestTransaction($merchantRef, $amount) {
    $instance = new self();
    $data = [
      'method'         => 'QRISC',
      'merchant_ref'   => $merchantRef,
      'amount'         => $amount,
      'customer_name'  => 'Nama Pelanggan',
      'customer_email' => 'emailpelanggan@domain.com',
      'customer_phone' => '081234567890',
      'order_items'    => [
        [
          'sku'         => 'FB-06',
          'name'        => 'Nama Produk 1',
          'price'       => 50000,
          'quantity'    => 1,
          'product_url' => 'https://tokokamu.com/product/nama-produk-1',
          'image_url'   => 'https://tokokamu.com/product/nama-produk-1.jpg',
        ],
        [
          'sku'         => 'FB-07',
          'name'        => 'Nama Produk 2',
          'price'       => 50000,
          'quantity'    => 1,
          'product_url' => 'https://tokokamu.com/product/nama-produk-2',
          'image_url'   => 'https://tokokamu.com/product/nama-produk-2.jpg',
        ]
      ],
      'return_url'   => 'https://domainanda.com/redirect',
      'expired_time' => (time() + (24 * 60 * 60)), // 24 hour
      'signature'    => self::signature($merchantRef, $amount)
    ];

    $response = $instance->tripay->post('/transaction/create', $data);

    if (!$response->successful()) {
      Log::channel('tripay')->error('requestTransaction: ' . $response->body());
      return [];
    }

    return $response->json();
  }

}