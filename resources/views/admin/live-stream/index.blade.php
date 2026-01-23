@extends('layouts.app', ['type' => 'admin'])

@section('css')
    <link rel="stylesheet" href="{{ asset('css/live-stream.css') }}">
    <style>
        .stream-card {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .stream-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
        }

        .stream-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-live {
            background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            color: white;
        }

        .status-scheduled {
            background: #fbbf24;
            color: #78350f;
        }

        .status-ended {
            background: #e5e7eb;
            color: #6b7280;
        }

        .video-preview-container {
            position: relative;
            width: 100%;
            max-width: 640px;
            height: 360px;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            margin: 20px 0;
        }

        #camera-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .controls-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
            padding: 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .stream-stats {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }

        .stat-card {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .device-selector {
            margin: 15px 0;
        }
    </style>
@endsection

@section('js')
    <!-- Agora SDK -->
    <script src="https://download.agora.io/sdk/release/AgoraRTC_N-4.20.0.js"></script>
    <script src="{{ asset('js/live-stream-host.js') }}"></script>
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

                <!-- Stream History -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Stream History</h5>
                        <div id="stream-history">
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-broadcast" style="font-size: 48px;"></i>
                                <p class="mt-3">No streams yet. Start your first live stream!</p>
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
