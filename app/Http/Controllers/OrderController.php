<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller {

  public function show($orderCode) {
    $order = Order::whereCode($orderCode)->firstOrFail();
    return view('homepage.order.show', compact('order'));
  }

}
