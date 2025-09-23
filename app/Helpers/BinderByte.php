<?php

namespace App\Helpers;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BinderByte {

  public static function trackAwb($courier, $awb) {
    $response = Http::get(env('BINDERBYTE_API_URL') . '/v1/track', [
      'api_key' => env('BINDERBYTE_API_KEY'),
      'courier' => $courier,
      'awb' => $awb,
    ]);

    if (!$response->successful()) {
      Log::channel('binderbyte')->info('request', [$courier, $awb]);
      Log::channel('binderbyte')->error($response->body());
      return false;
    }

    return $response->json();
  }

  public static function trackOrder(Order $order) {
    $preferredDelivery = explode(' - ', $order->courier);
    if (!count($preferredDelivery)) {
      Log::error('preferredDelivery not found', ['order_id' => $order->id]);
      return;
    }

    $shouldCheck = $order->resi_last_track_at == null;
    if ($order->resi_last_track_at) {
      $intervalChecking = 60 * 4; // 4 hours;
      $lastCheckAt = Carbon::parse($order->resi_last_track_at);
      $shouldCheck = $lastCheckAt->diffInMinutes(now()) > $intervalChecking;
    }

    if (!$shouldCheck) {
      return;
    }

    $courier = strtolower($preferredDelivery[0]);
    // TODO: might require key mapping for few couriers, because courier naming from Komerce and BinderByte might be different
    $track = self::trackAwb($courier, $order->resi_number);
    if ($track) {
      $order->resi_track_response = json_encode($track);
      $order->resi_last_track_at = now();
      $order->save();
    }
  }

}