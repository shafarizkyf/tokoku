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
        <div class="import-container">
          <div class="mb-3" id="dropzone">
            <p>Select/Drop JSON file to import your products</p>
          </div>
          <div class="alert alert-dark d-none" role="alert" id="import-information">
            <span id="item-selected-count">1</span>/<span id="item-total-count">100</span> Selected
          </div>
          <div class="d-grid gap-2">
            <button class="btn btn-primary btn-block d-none" id="btn-import">Import</button>
            <button class="btn btn-outline-secondary btn-block d-none" id="btn-reset">Reset</button>
          </div>
        </div>
      </div>
      <div class="col-md-10">
        <div class="d-flex flex-wrap gap-3" id="preview-container"></div>
      </div>
    </div>
  </div>
@endsection
