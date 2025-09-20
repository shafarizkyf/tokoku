<?php

namespace App\Http\Controllers\API;

use App\Helpers\DataTable;
use App\Helpers\Komerce;
use App\Helpers\Tripay;
use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller {

  public function index() {
    $orders = Order::with([
      'orderDetails:id,order_id,product_id',
      'orderDetails.product:id,name,slug',
      'orderDetails.product.image',
    ]);

    return DataTable::ajaxTable($orders);
  }

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
      $totalPrice = 0;
      $totalWeightInGrams = 0;
      $orderItems = [];
      foreach($request->items as $item) {
        $productVariation = ProductVariation::with(['product'])->find($item['product_variation_id']);
        $subtotal = floor($productVariation->price * $item['quantity']);

        // remove decimal like 999.99 to 999 as indonesian currency does not apply this
        $totalPrice += floor($subtotal);
        $totalWeightInGrams += $productVariation->weight ?? 500;
        $orderItems[] = [
          'product_variation' => $productVariation,
          'quantity' => $item['quantity'],
          'subtotal' => $subtotal,
        ];
      }

      $shippingPrice = $preferredDelivery['shipping_cost'];
      $grandTotal = $totalPrice + $shippingPrice;

      $order = new Order;
      $order->user_id = Auth::id();
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

      foreach($orderItems as $orderItem) {
        $orderDetail = new OrderDetail;
        $orderDetail->order_id = $order->id;
        $orderDetail->product_id = $orderItem['product_variation']->product_id;
        $orderDetail->product_variation_id = $orderItem['product_variation']->id;
        $orderDetail->name_snapshot = $orderItem['product_variation']->product->name;
        $orderDetail->price = $orderItem['product_variation']->price;
        $orderDetail->quantity = $orderItem['quantity'];
        $orderDetail->subtotal = $orderItem['subtotal'];
        $orderDetail->weight = 500;
        $orderDetail->save();
      }

      $response = Tripay::requestTransaction($order);
      $order->payment_response = json_encode($response);
      $order->save();

      if (!$response['success']) {
        $response = response([
          'success' => false,
          'message' => $response['message'],
        ], 400);
      } else {

        // clear cart items
        $cart = Cart::whereSessionId(Auth::id())->first();
        if ($cart) {
          $cart->items()->delete();
        }

        $response = response([
          'success' => true,
          'message' => 'Pesanan diterima',
          'data' => [
            'url' => route('orders.details', ['orderCode' => $order->code]),
          ],
        ], 200);
      }
    });

    return $response;
  }

}
