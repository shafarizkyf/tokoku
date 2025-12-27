@extends('layouts.app', ['type' => 'admin'])

@section('css')

@endsection

@section('js')
  <script src="{{ asset('js/product/index.js') }}"></script>
@endsection

@section('content')
  <div class="container py-5">
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-5">
              <h5 class="card-title m-0">Data Produk</h5>
              <div class="btn-group">
                <a href="{{ route('products.add') }}" class="btn btn-primary">Buat Baru</a>
                <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                  <span class="visually-hidden">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu">
                  <li><a class="dropdown-item" href="{{ route('products.import') }}">Import</a></li>
                  <li><a class="dropdown-item" href="{{ route('products.bulk_discount') }}">Diskon Massal</a></li>
                </ul>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-hover table-bordered" id="table-products">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Harga</th>
                    <th>Stok</th>
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