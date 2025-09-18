@extends('layouts.app', ['type' => 'homepage'])

@section('css')
  <link rel="stylesheet" href="{{ asset('css/cart.css') }}">
@endsection

@section('js')
  <script src="{{ asset('js/region.js') }}"></script>
  <script src="{{ asset('js/homepage/cart.js') }}"></script>
@endsection

@section('content')
  <div class="container cart">
    <h1 class="h3">Keranjang</h1>
    <div class="row mt-4">
      <div class="col-md-8">
        <div class="d-grid gap-3">
          <div class="cart-items"></div>
          <div class="card">
            <div class="card-body">
              <div class="d-grid gap-2 mb-2">
                <div class="d-flex align-items-center justify-content-between">
                  <h6>Pengiriman</h6>
                <span role="button" class="text-primary text-decoration-underline" id="btn-set-shipping">Atur alamat</span>
                </div>
                <div class="alert alert-light" id="selected-address">
                  Mohon atur alamat pengiriman
                </div>
              </div>
              <div class="d-grid gap-2 d-none" id="shipping-form">
                <input type="text" class="form-control" name="receiver_name" id="receiver_name" placeholder="Nama Penerima">
                <select class="" name="province_id" id="province_id">
                  <option value="">Provinsi</option>
                </select>
                <select class="" name="regency_id" id="regency_id">
                  <option value="">Kota/Kabupaten</option>
                </select>
                <select class="" name="district_id" id="district_id">
                  <option value="">Kecamatan</option>
                </select>
                <select class="" name="village_id" id="village_id">
                  <option value="">Kelurahan/Desa</option>
                </select>
                <select class="" name="postal_code" id="postal_code">
                  <option value="">Kodepos</option>
                </select>
                <input type="text" class="form-control" placeholder="Alamat (contoh: Jl. Ahmad Yani RT.01 RW.01)" name="address" id="address">
                <input type="text" class="form-control" placeholder="Catatan tambahan" name="shipping_note" id="shipping_note" maxlength="100">
              </div>
              <div class="d-grid gap-2" id="delivery-options">
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card">
          <div class="card-body">
            <div class="d-grid gap-3">
              <div class="d-grid">
                <h6>Pembayaran</h6>
                <select class="" name="payment_method" id="payment_method">
                  <option value="">Metode Pembayaran</option>
                </select>
              </div>
              @component('components.transaction-details')
                <div class="d-grid mt-3">
                  <button class="btn btn-primary" id="btn-pay">Bayar</button>
                </div>
              @endcomponent
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection