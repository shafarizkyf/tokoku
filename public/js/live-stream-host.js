/**
 * Live Stream Host Interface
 * Handles camera, microphone, and stream management for hosts
 */

class LiveStreamHost {
    constructor() {
        this.agoraClient = null;
        this.localTracks = {
            videoTrack: null,
            audioTrack: null
        };
        this.currentStream = null;
        this.isLive = false;
        this.streamStartTime = null;
        this.durationInterval = null;
        this.statsInterval = null;
        this.devices = {
            cameras: [],
            microphones: []
        };
        this.beforeUnloadHandler = this.beforeUnloadHandler.bind(this);
    }

    /**
     * Initialize the host interface
     */
    async init() {
        try {
            // Check for interrupted stream (page reload)
            await this.handleInterruptedStream();

            // Get Agora App ID
            this.appId = document.querySelector('meta[name="agora-app-id"]')?.content;

            if (!this.appId) {
                console.error('Agora App ID not found');
                return;
            }

            // Initialize Agora client
            this.agoraClient = AgoraRTC.createClient({ mode: 'live', codec: 'vp8' });
            await this.agoraClient.setClientRole('host');

            // Load devices
            await this.loadDevices();

            // Load stream history
            await this.loadStreamHistory();

            // Check for active stream
            await this.checkActiveStream();

            console.log('Host interface initialized');
        } catch (error) {
            console.error('Failed to initialize host interface:', error);
        }
    }

    /**
     * Handle interrupted stream on page reload
     */
    async handleInterruptedStream() {
        try {
            const result = await $.getJSON('/api/live-streams/current');

            if (result.success && result.data) {
                // There's an active stream - it was interrupted by page reload
                const stream = result.data;

                // End the stream automatically
                await this.endInterruptedStream(stream.id);

                // Clear session storage
                sessionStorage.removeItem('activeStream');

                // Show notification
                this.showStreamEndedNotification(stream.title);
            } else {
                // Check if there's a stream in sessionStorage that's no longer active
                const storedStream = sessionStorage.getItem('activeStream');
                if (storedStream) {
                    sessionStorage.removeItem('activeStream');
                }
            }
        } catch (error) {
            console.error('Failed to check for interrupted stream:', error);
        }
    }

    /**
     * End an interrupted stream
     */
    async endInterruptedStream(streamId) {
        try {
            const token = document.querySelector('meta[name="token"]')?.content;

            await fetch(`/api/live-streams/${streamId}/stop`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });

            // Clear cache
            fetch('/api/live-streams/active', { method: 'GET' }).catch(() => {});
        } catch (error) {
            console.error('Failed to end interrupted stream:', error);
        }
    }

    /**
     * Show notification when stream was ended due to page reload
     */
    showStreamEndedNotification(streamTitle) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'alert alert-warning alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 z-index-5000';
        notification.style.zIndex = '9999';
        notification.innerHTML = `
            <strong>Stream Interrupted!</strong> Your stream "${streamTitle}" was automatically ended because the page was reloaded.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(notification);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    /**
     * Load available cameras and microphones
     */
    async loadDevices() {
        try {
            const devices = await AgoraRTC.getDevices();

            this.devices.cameras = devices.filter(d => d.kind === 'videoinput');
            this.devices.microphones = devices.filter(d => d.kind === 'audioinput');

            // Populate camera select
            const cameraSelect = document.getElementById('camera-select');
            if (cameraSelect) {
                cameraSelect.innerHTML = this.devices.cameras.map((camera, index) =>
                    `<option value="${camera.deviceId}">${camera.label || `Camera ${index + 1}`}</option>`
                ).join('');
            }

            // Populate microphone select
            const micSelect = document.getElementById('microphone-select');
            if (micSelect) {
                micSelect.innerHTML = this.devices.microphones.map((mic, index) =>
                    `<option value="${mic.deviceId}">${mic.label || `Microphone ${index + 1}`}</option>`
                ).join('');
            }

            // Setup preview
            await this.setupPreview();

            // Initialize product select with Selectize
            await this.initProductSelect();
        } catch (error) {
            console.error('Failed to load devices:', error);
        }
    }

    /**
     * Setup camera preview in modal
     */
    async setupPreview() {
        try {
            const cameraSelect = document.getElementById('camera-select');
            const previewVideo = document.getElementById('preview-video');

            if (!cameraSelect || !previewVideo) return;

            // Create video track for preview
            const videoTrack = await AgoraRTC.createCameraVideoTrack({
                cameraId: cameraSelect.value
            });

            videoTrack.play(previewVideo);

            // Update preview when camera changes
            cameraSelect.addEventListener('change', async () => {
                videoTrack.stop();
                const newTrack = await AgoraRTC.createCameraVideoTrack({
                    cameraId: cameraSelect.value
                });
                newTrack.play(previewVideo);
            });
        } catch (error) {
            console.error('Failed to setup preview:', error);
        }
    }

    /**
     * Initialize product select with Selectize
     */
    async initProductSelect() {
        const productSelect = document.getElementById('stream-products');
        if (!productSelect) return;

        $(productSelect).selectize({
            plugins: ['remove_button'],
            valueField: 'id',
            labelField: 'name',
            searchField: 'name',
            maxItems: 10,
            load: (query, callback) => {
                if (!query.length) return callback();

                fetch(`/api/search?keyword=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        callback(data);
                    })
                    .catch(() => {
                        callback();
                    });
            },
            create: false,
            render: {
                option: (item, escape) => {
                    return `<div>${escape(item.name)}</div>`;
                }
            }
        });
    }

    /**
     * Start a new live stream
     */
    async startStream() {
        try {
            const title = document.getElementById('stream-title-input').value;
            const description = document.getElementById('stream-description').value;
            const productSelect = document.getElementById('stream-products');
            const productIds = productSelect?.selectize?.getValue() || [];

            if (!title) {
                alert('Please enter a stream title');
                return;
            }

            // Get token from meta tag
            const token = document.querySelector('meta[name="token"]')?.content;

            // Start stream via API
            const response = await fetch('/api/live-streams', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({
                    title,
                    description,
                    product_ids: productIds
                })
            });

            if (!response.ok) {
                throw new Error('Failed to start stream');
            }

            const result = await response.json();
            this.currentStream = result.data.stream;
            const agoraData = result.data.agora;

            // Store stream info in sessionStorage for reload handling
            sessionStorage.setItem('activeStream', JSON.stringify({
                id: this.currentStream.id,
                title: this.currentStream.title,
                startedAt: new Date().toISOString()
            }));

            // Add beforeunload warning
            this.addBeforeUnloadWarning();

            // Join Agora channel
            await this.joinChannel(agoraData);

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('startStreamModal'));
            modal.hide();

            // Show active stream section
            this.showActiveStream();

            // Start tracking
            this.startTracking();

            alert('Stream started successfully!');
        } catch (error) {
            console.error('Failed to start stream:', error);
            alert('Failed to start stream. Please try again.');
        }
    }

    /**
     * Add beforeunload warning to prevent accidental page reload
     */
    addBeforeUnloadWarning() {
        window.addEventListener('beforeunload', (e) => {
            if (this.isLive) {
                e.preventDefault();
                e.returnValue = 'You are currently streaming. Are you sure you want to leave? Your stream will be ended.';
                return e.returnValue;
            }
        });
    }

    /**
     * Remove beforeunload warning
     */
    removeBeforeUnloadWarning() {
        window.removeEventListener('beforeunload', this.beforeUnloadHandler);
    }

    /**
     * Beforeunload handler
     */
    beforeUnloadHandler(e) {
        if (this.isLive) {
            e.preventDefault();
            e.returnValue = 'You are currently streaming. Are you sure you want to leave? Your stream will be ended.';
            return e.returnValue;
        }
    }

    /**
     * Join Agora channel as host
     */
    async joinChannel(agoraData) {
        try {
            const cameraSelect = document.getElementById('camera-select');
            const micSelect = document.getElementById('microphone-select');

            // Create local tracks
            this.localTracks.videoTrack = await AgoraRTC.createCameraVideoTrack({
                cameraId: cameraSelect?.value
            });

            this.localTracks.audioTrack = await AgoraRTC.createMicrophoneAudioTrack({
                microphoneId: micSelect?.value
            });

            // Join channel
            await this.agoraClient.join(
                agoraData.app_id,
                agoraData.channel_name,
                agoraData.token,
                agoraData.uid
            );

            // Publish tracks
            await this.agoraClient.publish([
                this.localTracks.videoTrack,
                this.localTracks.audioTrack
            ]);

            // Play video locally
            const preview = document.getElementById('camera-preview');
            if (preview) {
                this.localTracks.videoTrack.play(preview);
            }

            this.isLive = true;
            console.log('Joined channel successfully');
        } catch (error) {
            console.error('Failed to join channel:', error);
            throw error;
        }
    }

    /**
     * Stop the current stream
     */
    async stopStream() {
        if (!confirm('Are you sure you want to end this stream?')) {
            return;
        }

        try {
            // Remove beforeunload warning
            this.removeBeforeUnloadWarning();

            // Clear session storage
            sessionStorage.removeItem('activeStream');

            const token = document.querySelector('meta[name="token"]')?.content;

            // Stop stream via API
            const response = await fetch(`/api/live-streams/${this.currentStream.id}/stop`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });

            if (!response.ok) {
                throw new Error('Failed to stop stream');
            }

            // Leave Agora channel
            await this.leaveChannel();

            // Stop tracking
            this.stopTracking();

            // Hide active stream section
            this.hideActiveStream();

            // Reload history
            await this.loadStreamHistory();

            const result = await response.json();
            alert(`Stream ended! Duration: ${Math.floor(result.data.duration / 60)} minutes, Peak viewers: ${result.data.peak_viewers}`);
        } catch (error) {
            console.error('Failed to stop stream:', error);
            alert('Failed to stop stream. Please try again.');
        }
    }

    /**
     * Leave Agora channel
     */
    async leaveChannel() {
        try {
            // Stop and close local tracks
            if (this.localTracks.videoTrack) {
                this.localTracks.videoTrack.stop();
                this.localTracks.videoTrack.close();
            }
            if (this.localTracks.audioTrack) {
                this.localTracks.audioTrack.stop();
                this.localTracks.audioTrack.close();
            }

            // Leave channel
            if (this.agoraClient) {
                await this.agoraClient.leave();
            }

            this.isLive = false;
            console.log('Left channel successfully');
        } catch (error) {
            console.error('Failed to leave channel:', error);
        }
    }

    /**
     * Toggle camera on/off
     */
    async toggleCamera() {
        if (!this.localTracks.videoTrack) return;

        const isEnabled = this.localTracks.videoTrack.enabled;
        await this.localTracks.videoTrack.setEnabled(!isEnabled);

        const icon = document.getElementById('camera-icon');
        if (icon) {
            icon.className = isEnabled ? 'bi bi-camera-video-off' : 'bi bi-camera-video';
        }
    }

    /**
     * Toggle microphone on/off
     */
    async toggleMicrophone() {
        if (!this.localTracks.audioTrack) return;

        const isEnabled = this.localTracks.audioTrack.enabled;
        await this.localTracks.audioTrack.setEnabled(!isEnabled);

        const icon = document.getElementById('mic-icon');
        if (icon) {
            icon.className = isEnabled ? 'bi bi-mic-mute' : 'bi bi-mic';
        }
    }

    /**
     * Show active stream section
     */
    showActiveStream() {
        const section = document.getElementById('active-stream-section');
        const titleEl = document.getElementById('stream-title');

        if (section) section.style.display = 'block';
        if (titleEl) titleEl.textContent = this.currentStream.title;
    }

    /**
     * Hide active stream section
     */
    hideActiveStream() {
        const section = document.getElementById('active-stream-section');
        if (section) section.style.display = 'none';
    }

    /**
     * Start tracking duration and stats
     */
    startTracking() {
        this.streamStartTime = Date.now();

        // Update duration every second
        this.durationInterval = setInterval(() => {
            const duration = Math.floor((Date.now() - this.streamStartTime) / 1000);
            const minutes = Math.floor(duration / 60);
            const seconds = duration % 60;

            const durationEl = document.getElementById('stream-duration');
            if (durationEl) {
                durationEl.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            }
        }, 1000);

        // Update stats every 5 seconds
        this.statsInterval = setInterval(() => {
            this.updateStats();
        }, 5000);
    }

    /**
     * Stop tracking
     */
    stopTracking() {
        if (this.durationInterval) {
            clearInterval(this.durationInterval);
            this.durationInterval = null;
        }
        if (this.statsInterval) {
            clearInterval(this.statsInterval);
            this.statsInterval = null;
        }
    }

    /**
     * Update stream statistics
     */
    async updateStats() {
        if (!this.currentStream) return;

        try {
            const response = await fetch(`/api/live-streams/${this.currentStream.id}/statistics`);
            const result = await response.json();

            if (result.success) {
                const stats = result.data;

                const viewerEl = document.getElementById('viewer-count');
                const peakEl = document.getElementById('peak-viewers');

                if (viewerEl) viewerEl.textContent = stats.current_viewers;
                if (peakEl) peakEl.textContent = stats.peak_viewers;
            }
        } catch (error) {
            console.error('Failed to update stats:', error);
        }
    }

    /**
     * Load stream history
     */
    async loadStreamHistory() {
        try {
            const token = document.querySelector('meta[name="token"]')?.content;

            // For now, we'll show a placeholder
            // In production, you'd create an endpoint to get user's stream history
            const historyEl = document.getElementById('stream-history');
            if (historyEl) {
                historyEl.innerHTML = `
          <div class="text-center text-muted py-5">
            <i class="bi bi-broadcast" style="font-size: 48px;"></i>
            <p class="mt-3">Stream history will appear here</p>
          </div>
        `;
            }
        } catch (error) {
            console.error('Failed to load stream history:', error);
        }
    }

    /**
     * Check if there's an active stream
     */
    async checkActiveStream() {
        // This would check if the user has an active stream
        // For now, we'll skip this
    }
}

// Initialize when DOM is ready
let hostStream;

document.addEventListener('DOMContentLoaded', async function () {
    hostStream = new LiveStreamHost();
    await hostStream.init();
});

// Export for global access
window.hostStream = hostStream;
