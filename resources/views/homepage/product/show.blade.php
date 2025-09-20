@extends('layouts.app', [
  'type' => 'homepage',
  'productId' => $product->id,
  'initVariationId' => $product->variation->id,
])

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
  <div class="container product" data-init-options="{{ json_encode($defaultVariantOptions) }}">
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

        <div class="d-grid gap-2 mt-5 description-wrapper">
          <h6 class="m-0">Deskripsi Produk</h6>
          <div class="description">
            @if(strlen($product->description))
              <p>{!! $product->description !!}</p>
            @else
              <p class="text-muted">Deskripsi tidak tersedia</p>
            @endif
          </div>
          <button class="btn-toggle text-muted d-none" type="button">
            <span class="icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-down" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M8 1a.5.5 0 0 1 .5.5v11.793l3.146-3.147a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 .708-.708L7.5 13.293V1.5A.5.5 0 0 1 8 1"/>
              </svg>
            </span>
            <span class="text">Lihat Selengkapnya</span>
          </button>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card">
          <div class="card-body">
            <div class="d-grid gap-2">
              <button type="button" class="btn btn-primary" name="btn-add-to-cart">+ Keranjang</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection