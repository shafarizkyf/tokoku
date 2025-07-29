@extends('layouts.app', ['type' => 'homepage'])

@section('css')
  <link rel="stylesheet" href="{{ asset('css/cart.css') }}">
@endsection

@section('js')
  <script src="{{ asset('js/homepage/cart.js') }}"></script>
@endsection

@section('content')
  <div class="container cart">
    <h1 class="h3">Keranjang</h1>
    <div class="row mt-4">
      <div class="col-md-9 cart-items">
      </div>
      <div class="col-md-3">
        <div class="card">
          <div class="card-body">

            <div class="d-grid">
              <button class="btn btn-primary">Beli</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection