<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class OrderController extends Controller {

  public function index() {
    return view('admin.order.index');
  }

  public function show($orderCode) {
    $order = Order::whereCode($orderCode)->firstOrFail();
    return view('homepage.order.show', compact('order'));
  }

  public function orderEmailPreview(Order $order) {
    return view('email.order', compact('order'));
  }

  public function invoice($orderCode) {
    $order = Order::whereCode($orderCode)->firstOrFail();
    $pdf = Pdf::loadView('pdf.invoice', compact('order'));
    $filename = config('app.name') . "_{$order->code}.pdf";
    return $pdf->download($filename);
  }

}
