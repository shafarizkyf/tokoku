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
              <div class="d-grid">
                <h6>Rincian Transaksi</h6>
                <dl class="row">
                  <dt class="col-sm-8 fw-normal">Total Harga (<span id="item-count">1</span> Barang)</dt>
                  <dd class="col-sm-4 text-md-end" id="cost-items">...</dd>
                  <dt class="col-sm-8 fw-normal">Biaya Pengiriman</dt>
                  <dd class="col-sm-4 text-md-end" id="cost-shipping">...</dd>
                  <dt class="col-sm-8 fw-normal">Biaya Pemrosesan
                    <i data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Biaya untuk memproses pembayaran">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                        <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
                      </svg>
                    </i>
                  </dt>
                  <dd class="col-sm-4 text-md-end" id="cost-proccessing">...</dd>
                  <dt class="col-sm-8 fw-normal fw-bold">Total Tagihan</dt>
                  <dd class="col-sm-4 text-md-end fw-bold" id="grand-total">...</dd>
                </dl>
                <div class="d-grid mt-3">
                  <button class="btn btn-primary" id="btn-pay">Bayar</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection