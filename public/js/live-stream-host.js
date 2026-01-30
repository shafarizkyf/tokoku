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

  async init() {
    try {
      await this.handleInterruptedStream();

      this.appId = document.querySelector('meta[name="agora-app-id"]')?.content;

      if (!this.appId) {
        console.error('Agora App ID not found');
        return;
      }

      this.agoraClient = AgoraRTC.createClient({ mode: 'live', codec: 'vp8' });
      await this.agoraClient.setClientRole('host');

      await this.loadDevices();
      await this.loadStreamHistory();
      await this.checkActiveStream();

      console.log('Host interface initialized');
    } catch (error) {
      console.error('Failed to initialize host interface:', error);
    }
  }

  async handleInterruptedStream() {
    try {
      const result = await $.getJSON('/api/live-streams/current');

      if (result.success && result.data) {
        await this.endInterruptedStream(result.data.id);
        sessionStorage.removeItem('activeStream');
        this.showStreamEndedNotification(result.data.title);
      } else {
        const storedStream = sessionStorage.getItem('activeStream');
        if (storedStream) {
          sessionStorage.removeItem('activeStream');
        }
      }
    } catch (error) {
      console.error('Failed to check for interrupted stream:', error);
    }
  }

  async endInterruptedStream(streamId) {
    try {
      const token = document.querySelector('meta[name="token"]')?.content;

      await fetch(`/api/live-streams/${streamId}/stop`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });

      fetch('/api/live-streams/active', { method: 'GET' }).catch(() => {});
    } catch (error) {
      console.error('Failed to end interrupted stream:', error);
    }
  }

  showStreamEndedNotification(streamTitle) {
    const notification = document.createElement('div');
    notification.className = 'alert alert-warning alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 z-index-5000';
    notification.style.zIndex = '9999';
    notification.innerHTML = `
      <strong>Stream Interrupted!</strong> Your stream "${streamTitle}" was automatically ended because the page was reloaded.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(notification);

    setTimeout(() => {
      notification.remove();
    }, 5000);
  }

  async loadDevices() {
    try {
      const devices = await AgoraRTC.getDevices();

      this.devices.cameras = devices.filter(d => d.kind === 'videoinput');
      this.devices.microphones = devices.filter(d => d.kind === 'audioinput');

      const cameraSelect = document.getElementById('camera-select');
      if (cameraSelect) {
        cameraSelect.innerHTML = this.devices.cameras.map((camera, index) =>
          `<option value="${camera.deviceId}">${camera.label || `Camera ${index + 1}`}</option>`
        ).join('');
      }

      const micSelect = document.getElementById('microphone-select');
      if (micSelect) {
        micSelect.innerHTML = this.devices.microphones.map((mic, index) =>
          `<option value="${mic.deviceId}">${mic.label || `Microphone ${index + 1}`}</option>`
        ).join('');
      }

      await this.setupPreview();
      await this.initProductSelect();
    } catch (error) {
      console.error('Failed to load devices:', error);
    }
  }

  async setupPreview() {
    try {
      const cameraSelect = document.getElementById('camera-select');
      const previewVideo = document.getElementById('preview-video');

      if (!cameraSelect || !previewVideo) return;

      const videoTrack = await AgoraRTC.createCameraVideoTrack({
        cameraId: cameraSelect.value
      });

      videoTrack.play(previewVideo);

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

      const token = document.querySelector('meta[name="token"]')?.content;

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

      sessionStorage.setItem('activeStream', JSON.stringify({
        id: this.currentStream.id,
        title: this.currentStream.title,
        startedAt: new Date().toISOString()
      }));

      this.addBeforeUnloadWarning();

      await this.joinChannel(agoraData);

      const modal = bootstrap.Modal.getInstance(document.getElementById('startStreamModal'));
      modal.hide();

      this.showActiveStream();
      this.connectChat();
      this.startTracking();

      alert('Stream started successfully!');
    } catch (error) {
      console.error('Failed to start stream:', error);
      alert('Failed to start stream. Please try again.');
    }
  }

  addBeforeUnloadWarning() {
    window.addEventListener('beforeunload', this.beforeUnloadHandler);
  }

  removeBeforeUnloadWarning() {
    window.removeEventListener('beforeunload', this.beforeUnloadHandler);
  }

  beforeUnloadHandler(e) {
    if (this.isLive) {
      e.preventDefault();
      e.returnValue = 'You are currently streaming. Are you sure you want to leave? Your stream will be ended.';
      return e.returnValue;
    }
  }

  async joinChannel(agoraData) {
    try {
      const cameraSelect = document.getElementById('camera-select');
      const micSelect = document.getElementById('microphone-select');

      this.localTracks.videoTrack = await AgoraRTC.createCameraVideoTrack({
        cameraId: cameraSelect?.value
      });

      this.localTracks.audioTrack = await AgoraRTC.createMicrophoneAudioTrack({
        microphoneId: micSelect?.value
      });

      await this.agoraClient.join(
        agoraData.app_id,
        agoraData.channel_name,
        agoraData.token,
        agoraData.uid
      );

      await this.agoraClient.publish([
        this.localTracks.videoTrack,
        this.localTracks.audioTrack
      ]);

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

  async stopStream() {
    if (!confirm('Are you sure you want to end this stream?')) {
      return;
    }

    try {
      this.removeBeforeUnloadWarning();
      sessionStorage.removeItem('activeStream');

      const token = document.querySelector('meta[name="token"]')?.content;

      const response = await fetch(`/api/live-streams/${this.currentStream.id}/stop`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });

      if (!response.ok) {
        throw new Error('Failed to stop stream');
      }

      await this.leaveChannel();
      this.disconnectChat();
      this.stopTracking();
      this.hideActiveStream();
      await this.loadStreamHistory();

      const result = await response.json();
      alert(`Stream ended! Duration: ${Math.floor(result.data.duration / 60)} minutes, Peak viewers: ${result.data.peak_viewers}`);
    } catch (error) {
      console.error('Failed to stop stream:', error);
      alert('Failed to stop stream. Please try again.');
    }
  }

  async leaveChannel() {
    try {
      if (this.localTracks.videoTrack) {
        this.localTracks.videoTrack.stop();
        this.localTracks.videoTrack.close();
      }
      if (this.localTracks.audioTrack) {
        this.localTracks.audioTrack.stop();
        this.localTracks.audioTrack.close();
      }

      if (this.agoraClient) {
        await this.agoraClient.leave();
      }

      this.isLive = false;
      console.log('Left channel successfully');
    } catch (error) {
      console.error('Failed to leave channel:', error);
    }
  }

  async toggleCamera() {
    if (!this.localTracks.videoTrack) return;

    const isEnabled = this.localTracks.videoTrack.enabled;
    await this.localTracks.videoTrack.setEnabled(!isEnabled);

    const icon = document.getElementById('camera-icon');
    if (icon) {
      icon.className = isEnabled ? 'bi bi-camera-video-off' : 'bi bi-camera-video';
    }
  }

  async toggleMicrophone() {
    if (!this.localTracks.audioTrack) return;

    const isEnabled = this.localTracks.audioTrack.enabled;
    await this.localTracks.audioTrack.setEnabled(!isEnabled);

    const icon = document.getElementById('mic-icon');
    if (icon) {
      icon.className = isEnabled ? 'bi bi-mic-mute' : 'bi bi-mic';
    }
  }

  showActiveStream() {
    const section = document.getElementById('active-stream-section');
    const titleEl = document.getElementById('stream-title');

    if (section) section.style.display = 'block';
    if (titleEl) titleEl.textContent = this.currentStream.title;
  }

  hideActiveStream() {
    const section = document.getElementById('active-stream-section');
    if (section) section.style.display = 'none';
  }

  startTracking() {
    this.streamStartTime = Date.now();

    this.durationInterval = setInterval(() => {
      const duration = Math.floor((Date.now() - this.streamStartTime) / 1000);
      const minutes = Math.floor(duration / 60);
      const seconds = duration % 60;

      const durationEl = document.getElementById('stream-duration');
      if (durationEl) {
        durationEl.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
      }
    }, 1000);

    this.statsInterval = setInterval(() => {
      this.updateStats();
    }, 5000);
  }

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

  async loadStreamHistory() {
    try {
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

  async checkActiveStream() {
  }

  connectChat() {
    liveStreamChat.setCurrentStream(this.currentStream.id);
    liveStreamChat.onMessage((username, message) => this.addChatMessage(username, message));
    liveStreamChat.onSystem((message) => this.addChatMessage('System', message, true));
    liveStreamChat.connect();
  }

  disconnectChat() {
    liveStreamChat.disconnect();
  }

  addChatMessage(username, message, isSystem = false) {
    const chatContainer = document.getElementById('chat-messages');
    if (!chatContainer) return;

    const messageEl = document.createElement('div');
    messageEl.className = 'chat-message mb-2';
    if (isSystem) messageEl.classList.add('text-muted', 'fst-italic');

    messageEl.innerHTML = `
      <strong>${this.escapeHtml(username)}:</strong> ${this.escapeHtml(message)}
    `;

    chatContainer.appendChild(messageEl);
    chatContainer.scrollTop = chatContainer.scrollHeight;
  }

  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  async sendChatMessage() {
    const input = document.getElementById('chat-input');
    if (!input || !input.value.trim() || !this.currentStream) return;

    const message = input.value.trim();
    input.value = '';

    const success = await liveStreamChat.sendMessage(message);
    if (!success) {
      this.addChatMessage('You', message);
    }
  }
}

let hostStream;

document.addEventListener('DOMContentLoaded', async function () {
  hostStream = new LiveStreamHost();
  await hostStream.init();

  const chatInput = document.getElementById('chat-input');
  if (chatInput) {
    chatInput.addEventListener('keypress', function (e) {
      if (e.key === 'Enter') {
        hostStream.sendChatMessage();
      }
    });
  }
});

window.hostStream = hostStream;
