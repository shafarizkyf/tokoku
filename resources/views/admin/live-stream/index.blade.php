@extends('layouts.app', ['type' => 'admin'])

@section('css')
    <link rel="stylesheet" href="{{ asset('css/live-stream.css') }}">
    <link rel="stylesheet" href="{{ asset('css/live-stream-host.css') }}">
@endsection

@section('js')
    <!-- Agora SDK -->
    <script src="https://download.agora.io/sdk/release/AgoraRTC_N-4.20.0.js"></script>
    <script src="{{ asset('js/live-stream-host.js') }}"></script>
    <script src="{{ asset('js/live-stream-past.js') }}"></script>
@endsection

@section('content')
    <div class="container py-5">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Live Streaming</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#startStreamModal">
                        <i class="bi bi-broadcast"></i> Start New Stream
                    </button>
                </div>

                <!-- Active Stream Section -->
                <div id="active-stream-section" style="display: none;">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title m-0">
                                    <span class="live-badge">
                                        <span class="live-dot"></span>
                                        LIVE
                                    </span>
                                    <span id="stream-title" class="ms-2"></span>
                                </h5>
                                <button class="btn btn-danger" onclick="hostStream.stopStream()">
                                    <i class="bi bi-stop-circle"></i> End Stream
                                </button>
                            </div>

                            <!-- Video Preview -->
                            <div class="video-preview-container">
                                <video id="camera-preview" autoplay muted></video>
                                <div class="controls-overlay">
                                    <button class="btn btn-light btn-sm" onclick="hostStream.toggleCamera()">
                                        <i class="bi bi-camera-video" id="camera-icon"></i>
                                    </button>
                                    <button class="btn btn-light btn-sm" onclick="hostStream.toggleMicrophone()">
                                        <i class="bi bi-mic" id="mic-icon"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Stream Stats -->
                            <div class="stream-stats">
                                <div class="stat-card">
                                    <div class="stat-value" id="viewer-count">0</div>
                                    <div class="stat-label">Current Viewers</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value" id="peak-viewers">0</div>
                                    <div class="stat-label">Peak Viewers</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value" id="stream-duration">00:00</div>
                                    <div class="stat-label">Duration</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Past Streams Section -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Past Streams</h5>
                        <div id="stream-history-container">
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-hourglass-split" style="font-size: 48px;"></i>
                                <p class="mt-3">Loading stream history...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Start Stream Modal -->
    <div class="modal fade" id="startStreamModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Start Live Stream</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="start-stream-form">
                        <div class="mb-3">
                            <label class="form-label">Stream Title *</label>
                            <input type="text" class="form-control" id="stream-title-input" required
                                placeholder="e.g., Flash Sale - Up to 50% Off!">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="stream-description" rows="3"
                                placeholder="Tell viewers what this stream is about..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Featured Products (Optional)</label>
                            <select class="form-select" id="stream-products" multiple>
                                <!-- Products will be loaded dynamically -->
                            </select>
                            <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple products</small>
                        </div>

                        <div class="device-selector">
                            <label class="form-label">Camera</label>
                            <select class="form-select" id="camera-select">
                                <option value="">Loading cameras...</option>
                            </select>
                        </div>

                        <div class="device-selector">
                            <label class="form-label">Microphone</label>
                            <select class="form-select" id="microphone-select">
                                <option value="">Loading microphones...</option>
                            </select>
                        </div>

                        <!-- Camera Preview in Modal -->
                        <div class="mt-3">
                            <label class="form-label">Preview</label>
                            <div
                                style="width: 100%; height: 300px; background: #000; border-radius: 8px; overflow: hidden;">
                                <video id="preview-video" autoplay muted
                                    style="width: 100%; height: 100%; object-fit: cover;"></video>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="hostStream.startStream()">
                        <i class="bi bi-broadcast"></i> Go Live
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
