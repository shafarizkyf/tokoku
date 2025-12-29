<?php

namespace App\Http\Controllers\API;

use App\Helpers\Gmail;
use App\Helpers\Stock;
use App\Helpers\Utils;
use App\Helpers\WhatsApp;
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
      $status = strtoupper(request('status'));

      switch ($status) {
        case 'PAID':
          $order->status = 'paid';
          $order->payment_status = 'paid';
          $order->paid_at = now();
          $order->save();

          // email receipt
          $emailTemplate = view('email.order', compact('order'))->render();
          Gmail::send($order->user->email, config('app.name'). ": Kuitansi Pesanan", $emailTemplate);

          // send notification to admin
          $amount = Utils::currencyFormat($order->grand_total);
          $notificationMessage = "Pesanan {$order->code} senilai: {$amount} telah dibayar." ;
          WhatsApp::sendText($notificationMessage);
        break;

        case 'FAILED':
          $order->status = 'pending';
          $order->payment_status = 'failed';
          $order->save();

          Stock::revert($order);
        break;

        case 'EXPIRED':
          $order->status = 'pending';
          $order->payment_status = 'expired';
          $order->save();

          Stock::revert($order);
        break;

        case 'REFUND':
          // TODO:
        break;
      }
    }
    return [];
  }

}
