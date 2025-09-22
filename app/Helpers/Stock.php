<?php

namespace App\Helpers;

use App\Models\Order;

class Stock {

  public static function revert(Order $order) {
    foreach($order->orderDetails as $orderItem) {
      $productVariation = $orderItem->productVariation;
      $productVariation->stock += $orderItem->quantity;
      $productVariation->save();
    }
  }

}