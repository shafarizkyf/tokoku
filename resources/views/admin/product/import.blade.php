@extends('layouts.app', ['type' => 'admin'])

@section('css')
  <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
@endsection

@section('js')
  <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
  <script src="{{ asset('js/product/import.js') }}"></script>
@endsection

@section('content')
  <div class="container">
    <div class="loader-full-container"><span class="loader"></span></div>
    <div class="row py-5 bg-light" style="min-height: 100vh">
      <div class="col-md-12">
        <div id="brand-container" class="text-center">
          <img class="d-block mx-auto mb-4" src="https://getbootstrap.com/docs/5.0/assets/brand/bootstrap-logo.svg">
          <h1 class="display-5 fw-bold">Your Store Name</h1>
          <div class="col-lg-6 mx-auto">
            <p class="lead mb-4">This section will be generated once you import your JSON file</p>
          </div>
        </div>
        <div class="import-container pt-3">
          <div class="mb-3" id="dropzone">
            <p>Select/Drop JSON file to import your products</p>
          </div>
        </div>
        <div class="d-grid mb-3 position-sticky-top bg-light py-3 d-none">
          <input type="text" class="form-control" name="search" id="search" placeholder="Search">
          <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="d-flex gap-3">
              <div class="form-check">
                <input class="form-check-input" type="radio" value="1" name="radio-selection" id="radio-select-all" checked>
                <label class="form-check-label" for="radio-select-all">
                  Select All
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" value="0" name="radio-selection" id="radio-deselect-all">
                <label class="form-check-label" for="radio-deselect-all">
                  Deselect All
                </label>
              </div>
              <div class="form-check d-none">
                <input class="form-check-input" type="checkbox" value="" id="cb-view-unselected">
                <label class="form-check-label" for="cb-view-unselected">
                  View Unselected
                </label>
              </div>
            </div>
            <div class="d-flex align-items-center gap-2">
              <div class="badge bg-primary d-none" role="alert" id="import-information">
                <span id="item-selected-count">1</span>/<span id="item-total-count">100</span> Selected
              </div>
              <button class="btn btn-primary d-none" id="btn-import">
                <div class="d-flex align-items-center">
                  <span style="opacity: 0.2;">Please Wait</span>
                  <span class="loader-2"></span>
                </div>
              </button>
              <button class="btn btn-outline-secondary d-none" id="btn-reset">Reset</button>
            </div>
          </div>
        </div>
        <div class="d-flex flex-wrap gap-3" id="preview-container"></div>
      </div>
    </div>
  </div>
@endsection
