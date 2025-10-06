@extends('layouts.app', ['type' => 'admin'])

@section('css')

@endsection

@section('js')
  <script src="{{ asset('js/order/index.js') }}"></script>
@endsection

@section('content')
  <div class="container py-5">
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-5">
              <h5 class="card-title m-0">Data Order</h5>
            </div>
            <div class="table-responsive">
              <table class="table table-hover table-bordered" id="table-orders">
                <thead>
                  <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Barang</th>
                    <th>Tagihan</th>
                    <th>Status</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  @component('components.modal', ['title' => 'Nomor Resi', 'id' => 'resiNumberModal'])
    <div class="modal-body">
      <input type="text" class="form-control mb-3" name="resi_number" id="resi_number" placeholder="Masukan nomor resi">
      <div class="d-grid">
        <button class="btn btn-primary" name="btn-save-resi-number">Simpan</button>
      </div>
    </div>
  @endcomponent

  @component('components.modal', ['title' => 'Konfirmasi', 'id' => 'cancelConfirmationModal'])
    <div class="modal-body">
      <p>Anda yakin akan membatalkan pesanan ini?</p>
      <div class="d-grid">
        <button class="btn btn-danger" name="btn-cancel-order">Ya, Batalkan</button>
      </div>
    </div>
  @endcomponent
@endsection