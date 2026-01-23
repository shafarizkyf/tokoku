/**
 * Agora Live Streaming Integration
 * Handles live stream viewing functionality
 */

class AgoraLiveStream {
  constructor(appId) {
    this.appId = appId;
    this.client = null;
    this.localTracks = {
      videoTrack: null,
      audioTrack: null
    };
    this.remoteUsers = {};
    this.isJoined = false;
  }

  /**
   * Initialize Agora client
   */
  async init() {
    try {
      // Create Agora client
      this.client = AgoraRTC.createClient({ mode: 'live', codec: 'vp8' });

      // Set client role to audience (viewer)
      await this.client.setClientRole('audience');

      // Register event handlers
      this.registerEventHandlers();

      console.log('Agora client initialized successfully');
      return true;
    } catch (error) {
      console.error('Failed to initialize Agora client:', error);
      return false;
    }
  }

  /**
   * Register event handlers for Agora client
   */
  registerEventHandlers() {
    // Handle user published (when host starts streaming)
    this.client.on('user-published', async (user, mediaType) => {
      await this.client.subscribe(user, mediaType);
      console.log('Subscribe success');

      if (mediaType === 'video') {
        const remoteVideoTrack = user.videoTrack;
        const playerContainer = document.getElementById('live-player');

        // Clear existing content
        playerContainer.innerHTML = '';

        // Play the remote video
        remoteVideoTrack.play(playerContainer);

        // Update viewer count
        this.updateViewerCount();
      }

      if (mediaType === 'audio') {
        const remoteAudioTrack = user.audioTrack;
        remoteAudioTrack.play();
      }

      this.remoteUsers[user.uid] = user;
    });

    // Handle user unpublished (when host stops streaming)
    this.client.on('user-unpublished', (user, mediaType) => {
      console.log('User unpublished:', user.uid);

      if (mediaType === 'video') {
        const playerContainer = document.getElementById('live-player');
        playerContainer.innerHTML = '<div class="text-white text-center p-5">Stream ended</div>';
      }

      delete this.remoteUsers[user.uid];
      this.updateViewerCount();
    });

    // Handle user left
    this.client.on('user-left', (user) => {
      console.log('User left:', user.uid);
      delete this.remoteUsers[user.uid];
      this.updateViewerCount();
    });

    // Handle connection state change
    this.client.on('connection-state-change', (curState, prevState) => {
      console.log(`Connection state changed from ${prevState} to ${curState}`);
    });
  }

  /**
   * Join a live stream channel
   * @param {string} channelName - The channel name to join
   * @param {string} token - The Agora token (can be null for testing)
   * @param {string|number} uid - User ID (can be null for auto-generation)
   */
  async join(channelName, token = null, uid = null) {
    try {
      if (!this.client) {
        await this.init();
      }

      // Join the channel
      await this.client.join(this.appId, channelName, token, uid);
      this.isJoined = true;

      console.log('Joined channel successfully:', channelName);
      return true;
    } catch (error) {
      console.error('Failed to join channel:', error);
      return false;
    }
  }

  /**
   * Leave the current channel
   */
  async leave() {
    try {
      if (this.client && this.isJoined) {
        await this.client.leave();
        this.isJoined = false;
        this.remoteUsers = {};

        console.log('Left channel successfully');
        return true;
      }
    } catch (error) {
      console.error('Failed to leave channel:', error);
      return false;
    }
  }

  /**
   * Update viewer count display
   */
  updateViewerCount() {
    const viewerCountEl = document.querySelector('.viewer-count-number');
    if (viewerCountEl) {
      const count = Object.keys(this.remoteUsers).length + 1; // +1 for current user
      viewerCountEl.textContent = count;
    }
  }

  /**
   * Get current channel statistics
   */
  async getChannelStats() {
    if (this.client && this.isJoined) {
      return await this.client.getRTCStats();
    }
    return null;
  }
}

// Live Stream UI Manager
class LiveStreamUI {
  constructor(agoraClient) {
    this.agoraClient = agoraClient;
    this.currentStream = null;
    this.chatMessages = [];
  }

  /**
   * Show floating live card
   * @param {Object} streamData - Stream information
   */
  showFloatingCard(streamData) {
    this.currentStream = streamData;

    const container = document.getElementById('live-stream-container');
    if (!container) return;

    container.innerHTML = `
      <div class="live-card">
        <div class="live-card-header">
          <div class="live-video-preview" id="live-preview-${streamData.id}">
            ${streamData.thumbnail ? `<img src="${streamData.thumbnail}" alt="Live preview" style="width: 100%; height: 100%; object-fit: cover;">` : ''}
          </div>
          <div class="live-badge">
            <span class="live-dot"></span>
            LIVE
          </div>
          <div class="viewer-count">
            <svg class="viewer-icon" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
              <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
            </svg>
            <span class="viewer-count-number">${streamData.viewers || 0}</span>
          </div>
        </div>
        <div class="live-card-body">
          <h3 class="live-title">${streamData.title}</h3>
          <div class="live-seller">
            <img src="${streamData.sellerAvatar || '/images/default-avatar.png'}" alt="${streamData.sellerName}" class="seller-avatar">
            <span class="seller-name">${streamData.sellerName}</span>
          </div>
          <div class="live-actions">
            <button class="btn-watch" onclick="liveStreamUI.openModal()">
              Watch Now
            </button>
            <button class="btn-close" onclick="liveStreamUI.hideFloatingCard()">
              ✕
            </button>
          </div>
        </div>
      </div>
    `;

    container.style.display = 'block';
  }

  /**
   * Hide floating live card
   */
  hideFloatingCard() {
    const container = document.getElementById('live-stream-container');
    if (container) {
      container.style.display = 'none';
    }
  }

  /**
   * Open live stream modal
   */
  async openModal() {
    if (!this.currentStream) return;

    const modal = document.getElementById('live-stream-modal');
    if (!modal) return;

    // Update modal content
    const modalTitle = modal.querySelector('.live-modal-title');
    if (modalTitle) {
      modalTitle.innerHTML = `
        <span class="live-dot"></span>
        ${this.currentStream.title}
      `;
    }

    // Show modal
    modal.classList.add('active');

    // Join the Agora channel
    const success = await this.agoraClient.join(
      this.currentStream.channelName,
      this.currentStream.token || null,
      null
    );

    if (success) {
      console.log('Successfully joined live stream');
      this.startChatSimulation(); // Simulate chat messages for demo
    } else {
      alert('Failed to join live stream. Please try again.');
      this.closeModal();
    }
  }

  /**
   * Close live stream modal
   */
  async closeModal() {
    const modal = document.getElementById('live-stream-modal');
    if (modal) {
      modal.classList.remove('active');
    }

    // Leave the Agora channel
    await this.agoraClient.leave();

    // Clear video player
    const playerContainer = document.getElementById('live-player');
    if (playerContainer) {
      playerContainer.innerHTML = '';
    }

    // Clear chat
    this.chatMessages = [];
    const chatContainer = document.querySelector('.chat-messages');
    if (chatContainer) {
      chatContainer.innerHTML = '';
    }
  }

  /**
   * Add chat message
   * @param {string} username - Username
   * @param {string} message - Message text
   */
  addChatMessage(username, message) {
    const chatContainer = document.querySelector('.chat-messages');
    if (!chatContainer) return;

    const messageEl = document.createElement('div');
    messageEl.className = 'chat-message';
    messageEl.innerHTML = `
      <div class="chat-username">${username}</div>
      <div class="chat-text">${message}</div>
    `;

    chatContainer.appendChild(messageEl);
    chatContainer.scrollTop = chatContainer.scrollHeight;

    this.chatMessages.push({ username, message, timestamp: Date.now() });
  }

  /**
   * Simulate chat messages for demo purposes
   */
  startChatSimulation() {
    const demoMessages = [
      { username: 'John', message: 'Hello everyone! 👋' },
      { username: 'Sarah', message: 'Great product!' },
      { username: 'Mike', message: 'How much is this?' },
      { username: 'Emma', message: 'Love the color! 😍' },
      { username: 'David', message: 'Is there a discount?' },
      { username: 'Lisa', message: 'Can you show it closer?' },
      { username: 'Tom', message: 'I want to buy this!' },
      { username: 'Anna', message: 'Amazing quality!' }
    ];

    let messageIndex = 0;
    const interval = setInterval(() => {
      if (!document.getElementById('live-stream-modal').classList.contains('active')) {
        clearInterval(interval);
        return;
      }

      const msg = demoMessages[messageIndex % demoMessages.length];
      this.addChatMessage(msg.username, msg.message);
      messageIndex++;
    }, 3000);
  }

  /**
   * Send chat message
   */
  sendChatMessage() {
    const input = document.getElementById('chat-input');
    if (!input || !input.value.trim()) return;

    const message = input.value.trim();
    this.addChatMessage('You', message);
    input.value = '';

    // Here you would send the message to your backend/chat service
    console.log('Sending message:', message);
  }
}

// Initialize when DOM is ready
let agoraClient;
let liveStreamUI;

document.addEventListener('DOMContentLoaded', function () {
  // Get Agora App ID from meta tag or environment
  const appId = document.querySelector('meta[name="agora-app-id"]')?.content;

  if (!appId) {
    console.warn('Agora App ID not found. Live streaming will not work.');
    return;
  }

  // Initialize Agora client
  agoraClient = new AgoraLiveStream(appId);
  liveStreamUI = new LiveStreamUI(agoraClient);

  // Setup chat input handler
  const chatInput = document.getElementById('chat-input');
  if (chatInput) {
    chatInput.addEventListener('keypress', function (e) {
      if (e.key === 'Enter') {
        liveStreamUI.sendChatMessage();
      }
    });
  }

  // Fetch active live streams from backend
  fetchActiveLiveStreams();
});

/**
 * Fetch active live streams from backend
 */
async function fetchActiveLiveStreams() {
  try {
    // Fetch from real API endpoint
    const response = await fetch('/api/live-streams/active');

    if (response.ok) {
      const streams = await response.json();

      // Show the first active stream if available
      if (streams && streams.length > 0) {
        const stream = streams[0];
        if (liveStreamUI) {
          liveStreamUI.showFloatingCard({
            id: stream.id,
            title: stream.title,
            channelName: stream.channel_name,
            sellerName: stream.seller_name,
            sellerAvatar: stream.seller_avatar,
            thumbnail: stream.thumbnail,
            viewers: stream.viewers,
            token: null // Token will be fetched when joining
          });
        }
      }
    } else {
      console.log('No active streams or API not available, showing demo stream');
      // showDemoStream();
    }
  } catch (error) {
    console.error('Failed to fetch live streams:', error);
    // Fallback to demo stream for development
    // showDemoStream();
  }
}

/**
 * Show demo stream for development/testing
 */
function showDemoStream() {
  setTimeout(() => {
    const sampleStream = {
      id: 1,
      title: 'Flash Sale - Up to 50% Off! 🔥',
      channelName: 'demo-channel-001',
      sellerName: 'TokoKu Official Store',
      sellerAvatar: 'https://ui-avatars.com/api/?name=TokoKu&background=667eea&color=fff',
      thumbnail: null,
      viewers: 127,
      token: null // In production, get this from your backend
    };

    if (liveStreamUI) {
      liveStreamUI.showFloatingCard(sampleStream);
    }
  }, 2000);
}

// Export for global access
window.agoraClient = agoraClient;
window.liveStreamUI = liveStreamUI;
