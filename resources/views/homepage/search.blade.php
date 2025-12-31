@extends('layouts.app', ['type' => 'homepage'])

@section('js')
    <script src="https://unpkg.com/infinite-scroll@5/dist/infinite-scroll.pkgd.min.js"></script>
    <script src="{{ asset('js/homepage/search.js') }}"></script>
@endsection

@section('content')
    <div class="container">
        <div class="row" style="margin-top: 80px;">
            <div class="col-md-12 py-3">
                <div class="product-list d-flex gap-3 flex-wrap"></div>
            </div>
        </div>
    </div>
@endsection
