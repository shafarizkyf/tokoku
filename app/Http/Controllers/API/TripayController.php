<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class TripayController extends Controller {

  public function callback() {
    Log::channel('tripay')->info('callback', request()->all());
    $order = Order::whereCode(request('merchant_ref'))->first();
    if ($order) {
      Log::channel('tripay')->info(request('status'), ['order_id' => $order->id]);
      $paymentResponse = $order->payment_response;
      $paymentResponse->callback = request()->all();
      $order->payment_response = json_encode($paymentResponse);

      switch (strtoupper(request('status'))) {
        case 'PAID':
          $order->status = 'paid';
          $order->payment_status = 'paid';
        break;

        case 'FAILED':
          $order->status = 'pending';
          $order->payment_status = 'failed';
        break;

        case 'EXPIRED':
          $order->status = 'pending';
          $order->payment_status = 'expired';
        break;

        case 'REFUND':
          // TODO:
        break;
      }

      $order->save();
    }
    return [];
  }

}
