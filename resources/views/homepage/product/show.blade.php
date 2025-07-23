@extends('layouts.app', ['type' => 'homepage'])

@section('css')
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
  <link rel="stylesheet" href="{{ asset('css/product.css') }}">
@endsection

@section('js')
  <script src="https://malaman.github.io/js-image-zoom/package/js-image-zoom.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
  <script src="{{ asset('js/product/shop.js') }}"></script>
@endsection

@section('content')
  <div class="container product" data-product-id="{{ $product->id }}" data-init-product-variation="{{ $product->variation->id }}">
    <div class="row">
      <div class="col-md-4">
        <div class="mb-3" id="main-img-preview">
          <img src="{{ $product->image->url }}" alt="{{ $product->name }}">
        </div>
        <div class="swiper" id="image-slider">
          <div class="swiper-wrapper">
            <div class="swiper-slide">Slide 1</div>
            <div class="swiper-slide">Slide 2</div>
            <div class="swiper-slide">Slide 3</div>
            <div class="swiper-slide">Slide 4</div>
            <div class="swiper-slide">Slide 5</div>
            <div class="swiper-slide">Slide 6</div>
            <div class="swiper-slide">Slide 7</div>
            <div class="swiper-slide">Slide 8</div>
            <div class="swiper-slide">Slide 9</div>
          </div>
          <div class="swiper-button-next"></div>
          <div class="swiper-button-prev"></div>
        </div>
      </div>
      <div class="col-md-5">
        <h1>{{ $product->name }}</h1>
        @if($product->variation->discount_price)
          <h2 class="fw-bolder">{{ App\Helpers\Utils::currencyFormat($product->variation->discount_price) }}</h2>
          <p class="text-decoration-line-through"><span class="badge bg-danger">{{ $product->variation->discount_percentage }}%</span> {{ App\Helpers\Utils::currencyFormat($product->variation->price) }}</p>
        @else
          <h2 class="fw-bolder">{{ App\Helpers\Utils::currencyFormat($product->variation->price) }}</h2>
        @endif

        <hr />

        @if(count(array_keys($variationOptions)))
          <div class="d-grid gap-3">
            @foreach(array_keys($variationOptions) as $attribute)
              <div class="attribute">
                <p class="mb-2"><span class="fw-bolder">{{ $attribute }}</span>: <span class="selected">-</span> </p>
                <div class="d-flex flex-wrap gap-2">
                  @foreach($variationOptions[$attribute] as $option)
                    <button type="button" class="btn btn-outline-dark" data-attribute="{{ $option['attribute_id'] }}" data-option="{{ $option['option_id'] }}">{{ $option['option_name'] }}</button>
                  @endforeach
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>
      <div class="col-md-3">
        <div class="card">
          <div class="card-body">
            <div class="d-grid gap-2">
              <button type="button" class="btn btn-primary">+ Keranjang</button>
              <button type="button" class="btn btn-outline-primary">Beli Sekarang</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection