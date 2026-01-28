@extends('layouts.app', ['type' => 'homepage'])

@section('css')
  <link rel="stylesheet" href="{{ asset('css/cart.css') }}">
@endsection

@section('js')
  <script src="{{ asset('js/homepage/checkout-session.js') }}"></script>
  <script>
    window.checkoutData = {
      hasPaymentUrl: {{ $has_payment_url ? 'true' : 'false' }},
      paymentUrl: "{{ $session->payment_url }}",
      recipientName: '{{ $session->recipient_name ?? '' }}',
      recipientEmail: '{{ $session->recipient_email ?? '' }}',
      recipientPhone: '{{ $session->recipient_phone ?? '' }}',
      addressDetail: '{{ $session->address_detail ?? '' }}',
      provinceId: {{ $session->province_id ?? 'null' }},
      regencyId: {{ $session->regency_id ?? 'null' }},
      districtId: {{ $session->district_id ?? 'null' }},
      villageId: {{ $session->village_id ?? 'null' }},
      postalCode: '{{ $session->postal_code ?? '' }}',
      note: '{{ $session->note ?? '' }}',
      courier: '{{ $session->courier ?? '' }}',
      serviceType: '{{ $session->service_type ?? '' }}',
      shippingPrice: {{ $session->shipping_price ?? 0 }},
      paymentMethod: '{{ $session->payment_method ?? '' }}',
    };
  </script>
@endsection

@section('content')
  <div class="container checkout-session">
    <h1 class="h3 mb-4">Checkout</h1>

    <div class="row py-5">
      <div class="col-md-8">
        <form id="checkout-form" action="#" method="POST">
          @csrf
          <input type="hidden" name="session_id" value="{{ $session_id }}">
          <input type="hidden" name="public_token" value="{{ $public_token }}">

          <div class="d-grid gap-4">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Informasi Penerima</h5>
              </div>
              <div class="card-body">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label for="recipient_name" class="form-label">Nama Penerima</label>
                    <input type="text" class="form-control" id="recipient_name" name="recipient_name" required>
                  </div>
                  <div class="col-md-3">
                    <label for="recipient_email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="recipient_email" name="recipient_email" required>
                  </div>
                  <div class="col-md-3">
                    <label for="recipient_phone" class="form-label">Nomor HP</label>
                    <input type="text" class="form-control" id="recipient_phone" name="recipient_phone" maxlength="20" required>
                  </div>
                  <div class="col-12">
                    <label for="address_detail" class="form-label">Alamat Lengkap</label>
                    <textarea class="form-control" id="address_detail" name="address_detail" rows="2" placeholder="Jl. Ahmad Yani RT.01 RW.01" required></textarea>
                  </div>
                  <div class="col-md-6">
                    <label for="province_id" class="form-label">Provinsi</label>
                    <select class="form-select" id="province_id" name="province_id" required>
                      <option value="">Pilih Provinsi</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label for="regency_id" class="form-label">Kota/Kabupaten</label>
                    <select class="form-select" id="regency_id" name="regency_id" required>
                      <option value="">Pilih Kota/Kabupaten</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label for="district_id" class="form-label">Kecamatan</label>
                    <select class="form-select" id="district_id" name="district_id" required>
                      <option value="">Pilih Kecamatan</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label for="village_id" class="form-label">Kelurahan/Desa</label>
                    <select class="form-select" id="village_id" name="village_id" required>
                      <option value="">Pilih Kelurahan/Desa</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label for="postal_code" class="form-label">Kodepos</label>
                    <input type="text" class="form-control" id="postal_code" name="postal_code" readonly required>
                  </div>
                  <div class="col-md-8">
                    <label for="note" class="form-label">Catatan (Opsional)</label>
                    <input type="text" class="form-control" id="note" name="note" maxlength="100" placeholder="Catatan tambahan">
                  </div>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Metode Pengiriman</h5>
              </div>
              <div class="card-body">
                <div id="shipping-options" class="d-grid gap-2">
                  <div class="alert alert-info">Opsi ekspedisi akan tampil setelah memasukan kodepos</div>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Metode Pembayaran</h5>
              </div>
              <div class="card-body">
                <select class="form-select" id="payment_method" name="payment_method" required>
                  <option value="">Pilih Metode Pembayaran</option>
                </select>
                <div id="payment-info" class="mt-2 text-muted small d-none"></div>
              </div>
            </div>
          </div>
        </form>
      </div>

      <div class="col-md-4">
        <div class="card sticky-top" style="top: 80px; z-index: 100;">
          <div class="card-header">
            <h5 class="mb-0">Ringkasan Pesanan</h5>
          </div>
          <div class="card-body">
            <div class="d-grid gap-3">
              <div id="cart-items">
                <div class="text-center text-muted">Memuat...</div>
              </div>

              <hr>

              <div class="d-flex justify-content-between">
                <span>Subtotal</span>
                <span id="summary-subtotal">Rp 0</span>
              </div>
              <div class="d-flex justify-content-between">
                <span>Ongkos Kirim</span>
                <span id="summary-shipping">Rp 0</span>
              </div>
              <div class="d-flex justify-content-between text-success">
                <span>Diskon</span>
                <span id="summary-discount">- Rp 0</span>
              </div>
              <div class="d-flex justify-content-between">
                <span>Biaya Pembayaran</span>
                <span id="summary-fee">Rp 0</span>
              </div>

              <hr>

              <div class="d-flex justify-content-between fw-bold">
                <span>Total</span>
                <span id="summary-total">Rp 0</span>
              </div>

              <button type="submit" form="checkout-form" class="btn btn-primary btn-lg" id="btn-pay">
                Bayar Sekarang
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
