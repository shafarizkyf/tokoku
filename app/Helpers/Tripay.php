<?php

namespace App\Helpers;

use App\Models\Order;
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

  public static function requestTransaction(Order $order) {
    if (Utils::hasTestHeaderKey()) {
      Log::info('Tripay::requestTransaction - returning mock data for order ' . $order->code);
      return json_decode(file_get_contents(storage_path('mock/tripay_request_transaction.json')), true);
    }

    $instance = new self();

    $orderItems = [];
    foreach($order->orderDetails as $orderItem) {
      $orderItems[] = [
        'sku'         => $orderItem->productVariation->sku,
        'name'        => $orderItem->name_snapshot,
        'price'       => floor($orderItem->price),
        'quantity'    => $orderItem->quantity,
        'product_url' => route('products.details', ['productSlug' => $orderItem->product->slug]),
        'image_url'   => 'https://tokokamu.com/product/nama-produk-1.jpg',
      ];
    }

    if ($order->shipping_price) {
      $orderItems[] = [
        'name'      => 'Pengiriman',
        'price'     => $order->shipping_price,
        'quantity'  => 1,
      ];
    }

    $data = [
      'method'          => $order->payment_method,
      'merchant_ref'    => $order->code,
      'amount'          => $order->grand_total,
      'customer_name'   => $order->recipient_name,
      'customer_email'  => $order->user->email,
      'customer_phone'  => $order->recipient_phone,
      'order_items'     => $orderItems,
      'return_url'      => route('orders.details', ['orderCode' => $order->code]),
      'expired_time'    => (time() + (24 * 60 * 60)), // 24 hour
      'signature'       => self::signature($order->code, $order->grand_total)
    ];

    $response = $instance->tripay->post('/transaction/create', $data);

    if (!$response->successful()) {
      Log::channel('tripay')->info('requestTransaction request: ', $data);
      Log::channel('tripay')->error('requestTransaction response: ' . $response->body());
      return $response->json();
    }

    return $response->json();
  }

}