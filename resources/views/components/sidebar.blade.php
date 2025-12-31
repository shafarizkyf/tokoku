<div class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark" style="width: 280px;">
  <a href="/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
    <svg class="bi me-2" width="40" height="32"><use xlink:href="#bootstrap"/></svg>
    <span class="fs-4">{{ config('app.name') }}</span>
  </a>
  <hr>
  <ul class="nav nav-pills flex-column mb-auto">
    <li class="nav-item">
      <a href="{{ route('orders.index') }}" class="nav-link text-white {{ request()->route()->getName() == 'orders.index' ? 'active' : '' }}" aria-current="page">
        Order
      </a>
    </li>
    @if($user->user_type == 'admin')
    <li class="nav-item">
      <a href="{{ route('products.index') }}" class="nav-link text-white {{ request()->route()->getName() == 'products.index' ? 'active' : '' }}" aria-current="page">
        <span class="ml-3">Produk</span>
      </a>
    </li>
    <li class="nav-item">
      <a href="{{ route('banners.index') }}" class="nav-link text-white {{ request()->route()->getName() == 'banners.index' ? 'active' : '' }}" aria-current="page">
        Banner
      </a>
    </li>
    <li class="nav-item">
      <a href="{{ route('banners.index') }}" class="nav-link text-white {{ request()->route()->getName() == 'banners.index' ? 'active' : '' }}" aria-current="page">
        Pengaturan
      </a>
    </li>
    @endif
  </ul>
  <hr>
  <div class="dropdown">
    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
      <strong>{{ $user->name }}</strong>
    </a>
    <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
      <li><a class="dropdown-item" href="{{ route('logout') }}">Logout</a></li>
    </ul>
  </div>
</div>