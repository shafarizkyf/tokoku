@extends('layouts.app', [
  'type' => 'admin'
])

@section('css')
  <link rel="stylesheet" href="{{ asset('css/order.css') }}">
@endsection

@section('content')
  <div class="container py-5">
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
                  <p class="m-0">{{ $order->full_address }}</p>
                </dd>
                <dt class="col-md-2 fw-normal text-muted">Phone Number</dt>
                <dd class="col-md-10">{{ $order->recipient_phone }}</dd>
              </dl>
            </div>
          </div>
          @if($order->resi_number)
            <div class="card">
              <div class="card-body">
                <h6>Lacak Paket</h6>
                @if($order->resi_track)
                  <dl class="row">
                    @foreach ($order->resi_track->history as $track)
                      <dt class="col-3 text-muted fw-normal">{{ Carbon\Carbon::parse($track->date)->format('d M Y H:i') }}</dt>
                      <dd class="col-9">{{ $track->desc }}</dd>
                    @endforeach
                  </dl>
                @else
                  <p class="m-0 text-muted">Data belum tersedia</p>
                @endif
              </div>
            </div>
          @endif
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
                  @if(str_contains($order->payment_method, 'VA'))
                    <dt class="col-sm-4 fw-normal">Nomor</dt>
                    <dd class="col-sm-8 text-md-end">{{ $order->payment_response->data->pay_code }}</dd>
                  @endif
                  @if(str_contains($order->payment_method, 'QR'))
                    <dt class="col-sm-4 fw-normal">QR</dt>
                    <dd class="col-sm-8 text-md-end">
                      <img src="{{ $order->payment_response->data->qr_url }}" class="tiny" />
                    </dd>
                  @endif
                </dl>
                <div class="d-grid">
                  <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#howToPay">Cara Bayar</button>
                </div>
              </div>
            </div>
          @endif
          <div class="card">
            <div class="card-body">
              @if($order->status != 'pending')
                <dl class="row mb-3">
                  <dt class="col-6 fw-normal">Status</dt>
                  <dd class="col-6 fw-bold text-end">{{ $order->order_status }}</dd>
                </dl>
              @endif
              @component('components.transaction-details', [
                'paymentMethod' => !$order->status == 'pending' ? $order->payment_response->data->payment_name : null,
                'itemCount' => $order->orderDetails->count(),
                'costItems' => App\Helpers\Utils::currencyFormat($order->total_price),
                'costShipping' => App\Helpers\Utils::currencyFormat($order->shipping_price),
                'costProcessing' => App\Helpers\Utils::currencyFormat($order->payment_fee),
                'grandTotal' => App\Helpers\Utils::currencyFormat($order->grand_total)
              ])
                @if($order->payment_status == 'paid')
                  <div class="d-grid">
                    <a class="btn btn-dark" href="{{ route('orders.invoice', ['orderCode' => $order->code])}}" target="_blank">Download Invoice</a>
                  </div>
                @endif
              @endcomponent
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  @component('components.modal', ['title' => 'Panduan Cara Bayar', 'id' => 'howToPay'])
    <div class="modal-body">
      <div class="accordion" id="accordionHowToPay">
        @foreach($order->payment_response->data->instructions as $index => $instruction)
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button {{ $index == 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}" aria-expanded="{{ $index == 0 ? 'true' : 'false' }}" aria-controls="collapse{{ $index }}">
                {{ $instruction->title }}
              </button>
            </h2>
            <div id="collapse{{ $index }}" class="accordion-collapse collapse {{ $index == 0 ? 'show' : '' }}" data-bs-parent="#accordionHowToPay">
              <div class="accordion-body">
                <ul>
                  @foreach ($instruction->steps as $step)
                    <li>{!! $step !!}</li>
                  @endforeach
                </ul>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  @endcomponent
@endsection
