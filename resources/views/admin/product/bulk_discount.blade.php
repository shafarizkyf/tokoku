@extends('layouts.app', ['type' => 'admin'])

@section('js')
  <script src="{{ asset('js/product/bulk_discount.js') }}"></script>
@endsection

@section('content')
  <div class="container py-5">
    <div class="row">
      <div class="col-md-12">
        <div class="card mb-4">
          <div class="card-body">
            <h5 class="card-title mb-4">Atur Diskon Massal</h5>
            <form id="form-discount" class="row align-items-end">
              <div class="col-md-4">
                <label class="form-label">Tipe Diskon</label>
                <select class="form-select" name="discount_type" id="discount_type">
                  <option value="percentage">Persentase (%)</option>
                  <option value="fixed">Nominal (Rp)</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Nilai Diskon</label>
                <input type="number" class="form-control" name="discount_value" id="discount_value" min="0" placeholder="Contoh: 10 atau 10000">
              </div>
              <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Terapkan Diskon</button>
              </div>
            </form>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h5 class="card-title m-0">Pilih Produk</h5>
              <div class="text-muted" id="selected-count">0 produk dipilih</div>
            </div>
            <div class="table-responsive">
              <table class="table table-hover table-bordered" id="table-products-select">
                <thead>
                  <tr>
                    <th width="50"><input type="checkbox" id="check-all"></th>
                    <th>Produk</th>
                    <th>Harga Saat Ini</th>
                    <th>Stok</th>
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
@endsection
