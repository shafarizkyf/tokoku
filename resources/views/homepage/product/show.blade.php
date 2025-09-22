@extends('layouts.app', [
  'type' => 'homepage',
  'productId' => $product->id,
  'initVariationId' => $product->variation->id,
  'title' => config('app.name') . ' | ' . $product->name
])

@section('meta')
  @component('components.seo-product-detail', [
    'description' => "Temukan {$product->name} harga murah hanya di " . config('app.name'),
    'url' => route('products.details', ['productSlug' => $product->slug]),
    'productName' => $product->name,
    'imageUrl' => $product->image->url,
  ])

  @endcomponent
@endsection

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
        @if($product->images->count() > 1)
          <div class="swiper" id="image-slider">
            <div class="swiper-wrapper">
              @foreach($product->images as $index => $image)
                <div class="swiper-slide cursor-pointer {{ $index == 0 ? 'border border-dark' : '' }}">
                  <img src="{{ $image->url }}" alt="" />
                </div>
              @endforeach
            </div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
          </div>
        @endif
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
        <div class="mt-3">
          <p class="mb-2"><span class="fw-bolder">Kuantitas</span>:</p>
          <div class="d-flex gap-1 my-2 quantity align-items-center">
            <button class="btn btn-sm btn-dark" name="substract">-</button>
            <input type="number" name="quantity" class="form-control border border-dark" placeholder="Qty" value="1" min="1" max="1">
            <button class="btn btn-sm btn-dark" name="add">+</button>
          </div>
        </div>

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
            <dl class="row">
              <dt class="col-4 text-muted fw-normal">Stok</dt>
              <dd class="col-8 text-end"><span id="stock-amount">-</span></dd>
              <dt class="col-4 text-muted fw-normal">Berat (Kg)</dt>
              <dd class="col-8 text-end"><span id="weight-amount">-</span></dd>
              <dt class="col-4 text-muted fw-normal">Subtotal</dt>
              <dd class="col-8 text-end"><span id="subtotal">-</span></dd>
            </dl>
            <div class="d-grid gap-2">
              <button type="button" class="btn btn-primary" name="btn-add-to-cart">+ Keranjang</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('js')
  <!-- Product structured data (JSON-LD for rich results) -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org/",
    "@type": "Product",
    "name": "{{ $product->name }}",
    "image": ["{{ $product->image->url }}"],
    "description": "{{ $product->description }}",
    "brand": {
      "@type": "Brand",
      "name": "{{ config('app.name') }}"
    },
    "offers": {
      "@type": "Offer",
      "url": "{{ route('products.details', ['productSlug' => $product->slug]) }}",
      "priceCurrency": "IDR",
      "price": "{{ $product->variation->price }}",
      "availability": "https://schema.org/InStock"
    }
  }
  </script>
@endsection
