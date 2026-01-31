@extends('layouts.app', ['type' => 'admin'])

@section('css')
  <link rel="stylesheet" href="{{ asset('css/admin/review.css') }}">
@endsection

@section('js')
  <script src="{{ asset('js/admin/review/index.js') }}"></script>
@endsection

@section('content')
  <div class="container py-5">
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h5 class="card-title m-0">Kelola Ulasan</h5>
              <div class="d-flex gap-2">
                <select class="form-select form-select-sm" id="filter-rating" style="width: 120px;">
                  <option value="">Semua Rating</option>
                  <option value="5">5 ★</option>
                  <option value="4">4 ★</option>
                  <option value="3">3 ★</option>
                  <option value="2">2 ★</option>
                  <option value="1">1 ★</option>
                </select>
                <select class="form-select form-select-sm" id="filter-status" style="width: 140px;">
                  <option value="">Semua Status</option>
                  <option value="approved">Approved</option>
                  <option value="pending">Pending</option>
                  <option value="rejected">Rejected</option>
                </select>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-hover table-bordered" id="table-reviews">
                <thead>
                  <tr>
                    <th>Produk</th>
                    <th>User</th>
                    <th>Rating</th>
                    <th>Ulasan</th>
                    <th>Gambar</th>
                    <th>Tanggal</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  @component('components.modal', ['title' => 'Detail Ulasan', 'id' => 'reviewDetailModal', 'size' => 'lg'])
    <div class="modal-body" id="review-detail-content">
      <!-- Content loaded via AJAX -->
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      <button type="button" class="btn btn-danger" id="btn-reject-review">Tolak</button>
      <button type="button" class="btn btn-success" id="btn-approve-review">Setuju</button>
    </div>
  @endcomponent

  @component('components.modal-confirm', ['id' => 'deleteReviewModal'])
    <p>Hapus ulasan ini?</p>
  @endcomponent
@endsection
