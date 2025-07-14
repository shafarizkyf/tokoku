@extends('layouts.app')

@section('css')
@endsection

@section('js')
  <script src="{{ asset('js/product/form.js') }}"></script>
@endsection

@section('content')
  <div class="container py-5">
    <input type="file" class="d-none" name="imported-file" id="imported-file" accept="application/json">
    <div class="d-flex align-items-center justify-content-between">
      <h5>Ubah Produk</h5>
      <button class="btn btn-outline-secondary" id="btn-import">Import</button>
    </div>
    <div class="card my-3">
      <div class="card-body">
        <h5 class="card-title">Informasi Produk</h5>
        <div class="row mt-5">
          <div class="col-md-12">
            <div class="row mb-4">
              <div class="col-md-4">
                <h6>Nama Produk</h6>
                <label for="product-name">Nama produk min. 25 karakter dengan memasukkan merek, jenis produk, warna, bahan, atau tipe.</label>
              </div>
              <div class="col-md-8">
                <input type="text" class="form-control" name="product-name" id="product-name">
              </div>
            </div>
            <div class="row mb-4">
              <div class="col-md-4">
                <h6>Deskripsi Produk</h6>
                <label for="product-description">Pastikan deskripsi produk memuat penjelasan detail terkait produkmu agar pembeli mudah mengerti dan menemukan produkmu.</label>
              </div>
              <div class="col-md-8">
                <textarea class="form-control" name="product-description" id="product-description" rows="5"></textarea>
              </div>
            </div>
            <div class="row mb-4">
              <div class="col-md-4">
                <h6>Kondisi Produk</h6>
                <label for="product-description">Pastikan deskripsi produk memuat penjelasan detail terkait produkmu agar pembeli mudah mengerti dan menemukan produkmu.</label>
              </div>
              <div class="col-md-8">
                <div class="d-flex gap-3">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="product-condition" id="condition-new" checked>
                    <label class="form-check-label" for="condition-new">
                      Baru
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="product-condition" id="condition-owned">
                    <label class="form-check-label" for="condition-owned">
                      Bekas
                    </label>
                  </div>
                </div>
              </div>
            </div>
            <div class="row mb-4">
              <div class="col-md-4">
                <h6>Harga Produk</h6>
              </div>
              <div class="col-md-8">
                <input type="text" class="form-control" name="product-price" id="product-price">
              </div>
            </div>
            <div class="row mb-4">
              <div class="col-md-4">
                <h6>Stok Produk</h6>
              </div>
              <div class="col-md-8">
                <input type="text" class="form-control" name="product-stock" id="product-stock">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="card mb-3">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-10">
            <h5 class="card-title">Varian</h5>
            <p>Tambah varian agar pembeli dapat memilih produk yang sesuai, yuk!</p>
          </div>
          <div class="col-md-2">
            <div class="d-grid">
              <button class="btn btn-outline-secondary">Tambah</button>
            </div>
          </div>
          <hr />
        </div>
        <div class="row gap-3 mb-5" id="variant-options"></div>
        <div class="table-responsive">
          <table class="table table-hover table-bordered">
            <thead>
              <tr>
                <th scope="col"></th>
                <th scope="col">Variant 1</th>
                <th scope="col">Harga</th>
                <th scope="col">Stok</th>
                <th scope="col">Berat</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1">
                  </div>
                </td>
                <td scope="row">Opsi 1</td>
                <td>
                  <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="text" class="form-control" placeholder="Harga">
                  </div>
                </td>
                <td>
                  <input type="text" class="form-control" placeholder="Stok">
                </td>
                <td>
                  <div class="input-group">
                    <input type="text" class="form-control" placeholder="Berat">
                    <span class="input-group-text">g</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="d-flex justify-content-end gap-3">
      <button class="btn btn-outline-secondary">Hapus</button>
      <button class="btn btn-primary">Simpan</button>
    </div>
  </div>
@endsection