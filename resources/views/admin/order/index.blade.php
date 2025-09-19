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
@endsection