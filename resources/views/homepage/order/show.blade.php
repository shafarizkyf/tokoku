@extends('layouts.app', [
  'type' => 'homepage'
])

@section('css')
  <link rel="stylesheet" href="{{ asset('css/order.css') }}">
@endsection

@section('content')
  <div class="container order">
    <h1 class="h3">Rincian Transaksi</h1>
    <div class="row mt-3">
      <div class="col-md-8">
        <div class="d-grid gap-3">
          @foreach($order->orderDetails as $orderItem)
            <div class="card">
              <div class="card-body">
                <div class="d-flex gap-3">
                  <img src="{{ $orderItem->product->image->url }}" class="img-thumbnail" alt="" style="height: 100px">
                  <div class="w-100">
                    <p class="m-0 fs-6 text-ellipsis">{{ $orderItem->name_snapshot }}</p>
                    @foreach($orderItem->productVariation()->options()->get() as $option)
                      <p class="m-0 variation badge rounded-pill text-bg-secondary">{{ $option->option_name }}</p>
                    @endforeach
                    <p class="m-0 text-muted">{{ $orderItem->quantity }} x {{ App\Helpers\Utils::currencyFormat($orderItem->price) }}</p>
                  </div>
                  <div class="d-flex">
                    <p class="m-0">{{ App\Helpers\Utils::currencyFormat($orderItem->subtotal) }}</p>
                  </div>
                </div>
              </div>
            </div>
          @endforeach
          <div class="card">
            <div class="card-body">
              <dl class="row">
                <dt class="col-md-2 fw-normal text-muted">Kurir</dt>
                <dd class="col-md-10">{{ $order->courier }}</dd>
                <dt class="col-md-2 fw-normal text-muted">Nomor Resi</dt>
                <dd class="col-md-10">{{ $order->resi_number ?? 'Belum tersedia' }}</dd>
                <dt class="col-md-2 fw-normal text-muted">Alamat</dt>
                <dd class="col-md-10">
                  <span class="fw-medium">{{ $order->recipient_name }}</span>
                  <p class="m-9">{{ $order->full_address }}</p>
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="d-grid gap-3">
          @if($order->status == 'pending')
            <div class="card">
              <div class="card-body">
                <h6>Menunggu Pembayaran</h6>
                <dl class="row">
                  <dt class="col-sm-4 fw-normal">Metode</dt>
                  <dd class="col-sm-8 text-md-end">{{ $order->payment_response->data->payment_name }}</dd>
                  <dt class="col-sm-4 fw-normal">Nomor</dt>
                  <dd class="col-sm-8 text-md-end">{{ $order->payment_response->data->pay_code }}</dd>
                </dl>
              </div>
            </div>
          @endif
          <div class="card">
            <div class="card-body">
              @component('components.transaction-details', [
                'paymentMethod' => !$order->status == 'pending' ? $order->payment_response->data->payment_name : null,
                'itemCount' => $order->orderDetails->count(),
                'costItems' => App\Helpers\Utils::currencyFormat($order->total_price),
                'costShipping' => App\Helpers\Utils::currencyFormat($order->shipping_price),
                'costProcessing' => App\Helpers\Utils::currencyFormat($order->payment_fee),
                'grandTotal' => App\Helpers\Utils::currencyFormat($order->grand_total)
              ]) @endcomponent
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
