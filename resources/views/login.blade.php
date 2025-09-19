@extends('layouts.app', ['type' => 'homepage'])


@section('content')
  <div class="container" style="margin-top: 100px">
    <div class="row">
      <div class="col-md-6 offset-md-3">
        <div class="card shadow-lg">
          <div class="card-body">
            <div class="d-grid gap-3">
              <h6>Silahkan login terlebih dahulu</h6>
              <a href="{{ route('oauth.google') }}" class="btn btn-primary">Lanjut dengan akun <span class="fw-bold">Google</span></a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
