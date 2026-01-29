<?php

namespace App\Helpers;

use App\Models\CheckoutSession;
use App\Models\Order;

class Stock {

  public static function revert(Order $order) {
    foreach($order->orderDetails as $orderItem) {
      $productVariation = $orderItem->productVariation;
      $productVariation->stock += $orderItem->quantity;
      $productVariation->save();
    }
  }

  public static function revertCheckoutSession(CheckoutSession $session) {
    foreach($session->items as $item) {
      $productVariation = $item->productVariation;
      if ($productVariation) {
        $productVariation->stock += $item->quantity;
        $productVariation->save();
      }
    }
  }

}