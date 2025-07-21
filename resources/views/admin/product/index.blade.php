@extends('layouts.app')

@section('css')

@endsection

@section('js')
  <script src="https://unpkg.com/infinite-scroll@5/dist/infinite-scroll.pkgd.min.js"></script>
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
              <a href="{{ route('products.import') }}" class="btn btn-outline-secondary">Import</a>
            </div>
            <div class="table-responsive">
              <table class="table table-hover table-bordered" id="table-products">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Harga</th>
                    <th>Stok</th>
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