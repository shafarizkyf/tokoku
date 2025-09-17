<?php

namespace App\Http\Controllers\API;

use App\Helpers\Komerce;
use App\Helpers\Tripay;
use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller {

  public function store(StoreOrderRequest $request) {
    Log::channel('order')->info('store', $request->all());

    $response = response([
      'success' => false,
      'message' => 'Unexpected Error'
    ], 500);

    $deliveryOptions = Komerce::calculateByPostalCode($request->shipping['postal_code']);
    $preferredDelivery = array_find($deliveryOptions, function($item)use ($request) {
      return $item['service_name'] == $request->delivery['service_name'] && $item['shipping_name'] == $request->delivery['shipping_name'];
    });

    if (!$preferredDelivery) {
      return response([
        'success' => false,
        'message' => 'Preferred delivery does not exist',
      ], 400);
    }

    DB::transaction(function()use ($request, $preferredDelivery, &$response){
      Tripay::requestTransaction('ABC', 100000);

      $totalPrice = 0;
      $totalWeightInGrams = 0;
      $orderItems = [];
      foreach($request->items as $item) {
        $productVariation = ProductVariation::with(['product'])->find($item['product_variation_id']);

        $totalPrice += $productVariation->price;
        $totalWeightInGrams += 500;
        $orderItems[] = $productVariation;
      }

      $shippingPrice = $preferredDelivery['shipping_cost'];
      $grandTotal = $totalPrice + $shippingPrice;

      $order = new Order;
      $order->code = 'INV' . now()->format('Ymd')  . Utils::generateRandomCode(3);
      $order->payment_method = $request->payment_method;
      $order->total_price = $totalPrice;
      $order->total_weight = $totalWeightInGrams;
      $order->shipping_price = $shippingPrice;
      $order->grand_total = $grandTotal;
      $order->courier = $preferredDelivery['shipping_name'] . ' - ' . $preferredDelivery['service_name'];
      $order->recipient_name = $request->shipping['receiver_name'];
      $order->recipient_phone = '0000';
      $order->province_id = $request->shipping['province_id'];
      $order->regency_id = $request->shipping['regency_id'];
      $order->district_id = $request->shipping['district_id'];
      $order->village_id = $request->shipping['village_id'];
      $order->postal_code = $request->shipping['postal_code'];
      $order->address_detail = $request->shipping['address'];
      $order->note = $request->shipping['shipping_note'];
      $order->save();

      foreach($orderItems as $index => $productVariation) {
        $quantity = $request->items[$index]['quantity'];
        $subtotal = $productVariation->price * $quantity;

        $orderDetail = new OrderDetail;
        $orderDetail->order_id = $order->id;
        $orderDetail->product_id = $productVariation->product_id;
        $orderDetail->product_variation_id = $productVariation->id;
        $orderDetail->name_snapshot = $productVariation->product->name;
        $orderDetail->price = $productVariation->price;
        $orderDetail->quantity = $quantity;
        $orderDetail->subtotal = $subtotal;
        $orderDetail->weight = 500;
        $orderDetail->save();
      }

      $response = response([
        'success' => true,
        'message' => 'Pesanan diterima'
      ], 200);
    });

    return $response;
  }

}
