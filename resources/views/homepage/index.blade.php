@extends('layouts.app', ['type' => 'homepage'])

@section('js')
  <script src="https://unpkg.com/infinite-scroll@5/dist/infinite-scroll.pkgd.min.js"></script>
  <script src="{{ asset('js/homepage/index.js') }}"></script>
@endsection

@section('content')
  <div class="container">
    @component('components.carousel') @endcomponent
    <div class="row">
      <div class="col-md-12">
        <div class="product-list d-flex gap-3 flex-wrap"></div>
      </div>
    </div>
  </div>
@endsection