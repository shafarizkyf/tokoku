@extends('layouts.app', ['type' => 'homepage'])

@section('css')
    <link rel="stylesheet" href="{{ asset('css/live-stream.css') }}">
@endsection

@section('js')
    <script src="https://unpkg.com/infinite-scroll@5/dist/infinite-scroll.pkgd.min.js"></script>
    <!-- Agora SDK -->
    <script src="https://download.agora.io/sdk/release/AgoraRTC_N-4.20.0.js"></script>
    <script src="{{ asset('js/live-stream.js') }}"></script>
    <script src="{{ asset('js/homepage/index.js') }}"></script>
@endsection

@section('content')
    <div class="container">
        @component('components.carousel', compact('banners'))
        @endcomponent
        <div class="row">
            <div class="col-md-12">
                <div class="product-list d-flex gap-3 flex-wrap"></div>
            </div>
        </div>
    </div>

    <!-- Floating Live Stream Card -->
    <div id="live-stream-container" class="live-stream-container" style="display: none;"></div>

    <!-- Live Stream Modal -->
    <div id="live-stream-modal" class="live-stream-modal">
        <div class="live-modal-content">
            <div class="live-modal-header">
                <h2 class="live-modal-title">
                    <span class="live-dot"></span>
                    Live Stream
                </h2>
                <button class="btn-close-modal" onclick="liveStreamUI.closeModal()">Close</button>
            </div>
            <div class="live-modal-body">
                <div class="live-products-container">
                    <div class="products-header">
                        <h3 class="products-title">Featured Products</h3>
                    </div>
                    <div class="products-list" id="live-stream-products">
                        <!-- Products will be injected here -->
                    </div>
                </div>
                <div class="live-video-container">
                    <div id="live-player"
                        style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: white;">
                        <div class="loading-spinner"></div>
                    </div>
                </div>
                <div class="live-chat-container">
                    <div class="chat-header">
                        <h3 class="chat-title">Live Chat</h3>
                    </div>
                    <div class="chat-messages"></div>
                    <div class="chat-input-container">
                        <input type="text" id="chat-input" class="chat-input" placeholder="Type a message..."
                            autocomplete="off">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
