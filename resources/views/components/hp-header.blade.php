<header>
  <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-light shadow-lg">
    <div class="container">
      <a class="navbar-brand text-dark" href="{{ url('/') }}">TokoKu</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarCollapse">
        <div class="position-relative w-100">
          <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search" />
          <ul class="search-result shadow-lg d-none"></ul>
        </div>
        <ul class="navbar-nav me-auto mb-2 mb-md-0">
          <li class="nav-item">
            <a class="nav-link text-dark" href="{{ route('carts.index') }}">
              <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" class="bi bi-bag" viewBox="0 0 16 16">
                <path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z"/>
              </svg>
              <span id="cart-counter" class="position-absolute translate-middle badge rounded-pill bg-danger" style="font-size: 9px;">0</span>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</header>