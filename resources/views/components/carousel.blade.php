<div id="myCarousel" class="carousel slide" data-bs-ride="carousel">
  @if(count($banners) > 1)
    <div class="carousel-indicators">
      @foreach($banners as $index => $banner)
        <button type="button" data-bs-target="#myCarousel" data-bs-slide-to="{{ $index }}" class="{{ $index == 0 ? 'active' : '' }}" aria-current="{{ $index == 0 ? 'true' : 'false' }}"></button>
      @endforeach
    </div>
  @endif
  <div class="carousel-inner">
    @foreach($banners as $index => $banner)
      <div class="carousel-item {{ $index == 0 ? 'active' : '' }}">
        <img src="{{ $banner->url }}">
      </div>
    @endforeach
  </div>
  @if(count($banners) > 1)
    <button class="carousel-control-prev" type="button" data-bs-target="#myCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#myCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Next</span>
    </button>
    @endif
</div>