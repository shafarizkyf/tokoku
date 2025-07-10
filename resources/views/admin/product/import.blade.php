@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
@endsection

@section('js')
  <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
  <script src="{{ asset('js/product-import.js') }}"></script>
@endsection

@section('content')
  <div class="container">
    <div class="row py-5 bg-light" style="min-height: 100vh">
      <div class="col-md-2 mb-3">
        <div class="import-container pt-3">
          <div class="mb-3" id="dropzone">
            <p>Select/Drop JSON file to import your products</p>
          </div>
          <div class="alert alert-dark d-none" role="alert" id="import-information">
            <span id="item-selected-count">1</span>/<span id="item-total-count">100</span> Selected
          </div>
          <div class="d-grid gap-2">
            <button class="btn btn-primary d-none" id="btn-import">Import</button>
            <button class="btn btn-outline-secondary d-none" id="btn-reset">Reset</button>
          </div>
        </div>
      </div>
      <div class="col-md-10">
        <div class="d-grid mb-3 position-sticky-top bg-light py-3 d-none">
          <input type="text" class="form-control" name="search" id="search" placeholder="Search">
          <div class="d-flex gap-3 mt-3">
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
        </div>
        <div class="d-flex flex-wrap gap-3" id="preview-container"></div>
      </div>
    </div>
  </div>
@endsection
