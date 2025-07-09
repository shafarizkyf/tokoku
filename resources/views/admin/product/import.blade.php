@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
@endsection

@section('js')
  <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
  <script src="{{ asset('js/product-import.js') }}"></script>
@endsection

@section('container')
  <div id="dropzone"></div>
@endsection
