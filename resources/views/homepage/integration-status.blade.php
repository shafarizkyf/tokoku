@extends('layouts.app')

@section('content')
    <div class="container" style="padding-top:100px">
        <div class="row g-4">
            @foreach($status as $item)
                <div class="col-md-6 col-lg-4">
                    <div class="card status-card {{ $item['is_configured'] ? 'configured' : '' }}">
                        <div class="card-body">
                            <h5 class="card-title">{{ $item['name'] }}</h5>
                            <p class="card-text text-muted">{{ $item['description'] }}</p>

                            @if($item['is_configured'])
                                <div class="text-success">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-check-circle" viewBox="0 0 16 16">
                                        <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zM6.333 11.667a.5.5 0 0 1 0-.833l2.667-2.667a.5.5 0 0 1 .707.707L7.39 10.293l1.667-1.667a.5.5 0 0 1 .707.707l-2.5 2.5a.5.5 0 0 1-.707 0z"/>
                                    </svg>
                                    <span>Terintegrasi</span>
                                </div>
                            @else
                                <div class="text-danger">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-exclamation-circle" viewBox="0 0 16 16">
                                        <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zM7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.095a.5.5 0 0 1-.7.106L5.828 4.5a.5.5 0 0 1 0-.707l..5.5707-.707a 0 0 1 .707 0l1.104 1.104a.5.5 0 0 1-.106.7L7.1 4.095z"/>
                                    </svg>
                                    <span>{{ $item['message'] }}</span>
                                </div>
                                @if(!empty($item['missing_envs']))
                                    <div class="env-list mt-2">
                                        <strong>Env yang diperlukan:</strong>
                                        <ul class="mb-0">
                                            @foreach($item['missing_envs'] as $env)
                                                <li>{{ $env }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection