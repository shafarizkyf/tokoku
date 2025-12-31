@extends('layouts.app', ['type' => 'admin'])

@section('js')
  <script src="{{ asset('js/shop/settings.js') }}"></script>
@endsection

@section('content')
  <div class="container py-5">
    <div class="row">
      <div class="col-md-8 mx-auto">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title mb-4">Pengaturan Toko</h5>
            
            <form id="shop-settings-form">
              <div class="mb-3">
                <label for="name" class="form-label">Nama Toko <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" required>
                <div class="invalid-feedback"></div>
              </div>

              <div class="mb-3">
                <label for="description" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                <div class="invalid-feedback"></div>
              </div>

              <div class="mb-3">
                <label for="image" class="form-label">Logo Toko</label>
                <input type="file" class="form-control" id="image" name="image" accept=".jpg,.jpeg,.png">
                <small class="form-text text-muted">Format: JPG, JPEG, PNG. Maksimal 2MB</small>
                <div class="invalid-feedback"></div>
              </div>

              <div class="mb-3" id="current-image-container" style="display: none;">
                <label class="form-label">Logo Saat Ini</label>
                <div>
                  <img id="current-image" src="" alt="Current shop logo" class="img-thumbnail" style="max-width: 200px;">
                </div>
              </div>

              <div class="mb-3" id="preview-container" style="display: none;">
                <label class="form-label">Preview Logo Baru</label>
                <div>
                  <img id="image-preview" src="" alt="Preview" class="img-thumbnail" style="max-width: 200px;">
                </div>
              </div>

              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary" id="btn-save">
                  <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                  Simpan Pengaturan
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
