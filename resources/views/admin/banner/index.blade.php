@extends('layouts.app', ['type' => 'admin'])

@section('js')
  <script src="{{ asset('js/banner/index.js') }}"></script>
@endsection

@section('content')
  <div class="container py-5">
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h5 class="card-title m-0">Data Produk</h5>
              <button class="btn btn-primary" name="btn-create-new">Buat Baru</button>
            </div>
            <div class="d-flex gap-3" id="previews">

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  @component('components.modal', ['title' => 'Buat Banner', 'id' => 'bannerInputModal'])
    <div class="modal-body">
      <div class="mb-3">
        <label for="image">Image</label>
        <input type="file" class="form-control" name="image" id="image" accept=".jpg, .png, .jpeg">
      </div>
      <div class="mb-3">
        <label for="link">Link</label>
        <input type="text" class="form-control" name="link" id="link">
      </div>
      <div class="mb-3">
        <label for="description">Description</label>
        <textarea class="form-control" name="description" id="description"></textarea>
      </div>
      <div class="d-grid">
        <button class="btn btn-primary" name="btn-save-banner">Simpan</button>
      </div>
    </div>
  @endcomponent
@endsection
