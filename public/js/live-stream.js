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
    this.ably = null;
    this.ablyChannel = null;
    this.isAblyConnected = false;
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

    // Fetch token from backend before joining
    let token = null;
    try {
      const joinResponse = await fetch(`/api/live-streams/${this.currentStream.id}/join`, {
        method: 'POST',
        body: JSON.stringify({}) // Add session_id if needed
      });
      if (joinResponse.ok) {
        const joinData = await joinResponse.json();
        token = joinData?.data?.agora?.token || null;
        const streamData = joinData?.data?.stream || {};

        // Update current stream data
        this.currentStream.token = token;

        // Render products
        if (streamData.products) {
          this.renderProducts(streamData.products);
        }
      } else {
        alert('Failed to get live stream token.');
        this.closeModal();
        return;
      }
    } catch (err) {
      console.error('Error fetching token:', err);
      alert('Failed to get live stream token.');
      this.closeModal();
      return;
    }

    // Join the Agora channel with the fetched token
    const success = await this.agoraClient.join(
      this.currentStream.channelName,
      token,
      null
    );

    if (success) {
      console.log('Successfully joined live stream');
      this.connectToChatChannel();
    } else {
      alert('Failed to join live stream. Please try again.');
      this.closeModal();
    }
  }

  /**
   * Render products in the sidebar
   */
  renderProducts(products) {
    const listContainer = document.getElementById('live-stream-products');
    if (!listContainer) return;

    listContainer.innerHTML = '';

    if (!products || products.length === 0) {
      listContainer.innerHTML = '<div style="padding: 20px; text-align: center; color: #a0aec0;">No products featured</div>';
      return;
    }

    products.forEach(product => {
      const price = currencyFormat.format(product.cheapest_variation.price);

      const el = document.createElement('div');
      el.className = 'live-product-card';
      el.innerHTML = `
        <img src="${product.image ? product.image.url : '/images/placeholder.png'}" class="product-thumb" alt="${product.name}">
        <div class="product-info">
          <h4 class="product-name" title="${product.name}">${product.name}</h4>
          <div class="product-price">
            <span class="price-current">${price}</span>
          </div>
          <button class="btn-buy-now" onclick="liveStreamUI.addToCart(${product.id})">
            Buy Now
          </button>
        </div>
      `;
      listContainer.appendChild(el);
    });
  }

  /**
   * Add product to cart
   */
  async addToCart(productId) {
    try {
      const response = await fetch('/api/carts', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        body: JSON.stringify({
          product_id: productId,
          quantity: 1
        })
      });

      const data = await response.json();

      if (response.ok) {
        // Show success animation/toast
        const btn = event.target;
        const originalText = btn.innerText;
        btn.innerText = 'Added!';
        btn.style.background = '#48bb78';
        setTimeout(() => {
          btn.innerText = originalText;
          btn.style.background = '';
        }, 2000);
      } else {
        if (response.status === 401) {
          if (confirm('You need to login to buy products. Go to login page?')) {
            window.location.href = '/login';
          }
        } else {
          alert(data.message || 'Failed to add to cart');
        }
      }
    } catch (e) {
      console.error('Add to cart error:', e);
      alert('Error adding to cart. Please try again.');
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

    this.disconnectFromChat();

    // Leave the Agora channel
    try {
      await fetch(`/api/live-streams/${this.currentStream.id}/leave`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        body: JSON.stringify({})
      });
    } catch (e) {
      console.error('Error leaving stream:', e);
    }

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
   * Add chat message to UI
   */
  addChatMessage(username, message, isSystem = false) {
    const chatContainer = document.querySelector('.chat-messages');
    if (!chatContainer) return;

    const messageEl = document.createElement('div');
    messageEl.className = 'chat-message';
    if (isSystem) messageEl.classList.add('system-message');

    messageEl.innerHTML = `
      <div class="chat-username">${username}</div>
      <div class="chat-text">${message}</div>
    `;

    chatContainer.appendChild(messageEl);
    chatContainer.scrollTop = chatContainer.scrollHeight;
  }

/**
     * Initialize Ably connection
     */
  async initAbly() {
    if (typeof Ably === 'undefined') {
      console.error('Ably library not loaded. Please include Ably.js in your HTML.');
      return false;
    }

    if (this.ably && this.isAblyConnected) {
      console.log('Ably already connected');
      return true;
    }

    try {
      console.log('Initializing Ably connection...');

      this.ably = new Ably.Realtime({
        authUrl: `/api/live-streams/ably/token?live_stream_id=${this.currentStream.id}`,
        queryTime: true
      });

      this.ably.connection.on('connecting', () => {
        console.log('Ably connecting...');
      });

      this.ably.connection.on('connected', () => {
        console.log('Ably connected successfully');
        this.isAblyConnected = true;
      });

      this.ably.connection.on('disconnected', () => {
        console.log('Ably disconnected');
        this.isAblyConnected = false;
      });

      this.ably.connection.on('suspended', () => {
        console.log('Ably suspended');
        this.isAblyConnected = false;
      });

      this.ably.connection.on('failed', (err) => {
        console.error('Ably connection failed:', err);
        this.isAblyConnected = false;
      });

      this.ably.connection.on('closing', () => {
        console.log('Ably closing connection');
      });

      this.ably.connection.on('closed', () => {
        console.log('Ably connection closed');
        this.isAblyConnected = false;
      });

      const state = this.ably.connection.state;
      console.log('Initial Ably connection state:', state);

      if (state === 'connected') {
        this.isAblyConnected = true;
        return true;
      }

      return new Promise((resolve, reject) => {
        const timeout = setTimeout(() => {
          console.error('Ably connection timeout, current state:', this.ably.connection.state);
          reject(new Error('Ably connection timeout'));
        }, 15000);

        const checkConnected = () => {
          if (this.ably.connection.state === 'connected') {
            clearTimeout(timeout);
            this.ably.connection.off('connected', checkConnected);
            this.ably.connection.off('failed', checkFailed);
            resolve(true);
          }
        };

        const checkFailed = (err) => {
          clearTimeout(timeout);
          this.ably.connection.off('connected', checkConnected);
          this.ably.connection.off('failed', checkFailed);
          console.error('Ably connection failed during connection attempt:', err);
          reject(err);
        };

        this.ably.connection.on('connected', checkConnected);
        this.ably.connection.on('failed', checkFailed);
      });
    } catch (e) {
      console.error('Failed to initialize Ably:', e);
      this.isAblyConnected = false;
      return false;
    }
  }


    /**
     * Connect to chat channel and subscribe to messages
     */
  async connectToChatChannel() {
    try {
      await this.initAbly();

      const channelName = `live-stream:${this.currentStream.id}`;

      this.ablyChannel = this.ably.channels.get(channelName);

      this.ablyChannel.subscribe('message', (message) => {
        this.addChatMessage(message.data.username, message.data.message);
        this.chatMessages.push({
          id: message.data.id,
          username: message.data.username,
          message: message.data.message,
          timestamp: new Date(message.data.created_at).getTime()
        });
      });

      this.ablyChannel.subscribe('system', (message) => {
        this.addChatMessage('System', message.data.message, true);
      });

      console.log('Subscribed to chat channel:', channelName);
    } catch (e) {
      console.error('Failed to connect to chat channel:', e);
      this.startChatPolling();
    }
  }

  /**
    * Disconnect from Ably chat channel
    */
  disconnectFromChat() {
    if (this.ablyChannel) {
      this.ablyChannel.unsubscribe();
      this.ably.channels.release(this.ablyChannel);
      this.ablyChannel = null;
    }

    if (this.ably) {
      this.ably.close();
      this.ably = null;
      this.isAblyConnected = false;
    }
  }

  /**
    * Poll for new chat messages (fallback)
    */
  startChatPolling() {
    if (this.chatPollInterval) clearInterval(this.chatPollInterval);

    this.chatPollInterval = setInterval(async () => {
      if (!this.currentStream) return;

      try {
        const response = await fetch(`/api/live-streams/${this.currentStream.id}/messages`);
        if (response.ok) {
          const messages = await response.json();
          // Filter new messages
          const newMessages = messages.filter(msg => {
            const msgTime = new Date(msg.created_at).getTime();
            const lastMsg = this.chatMessages[this.chatMessages.length - 1];
            return !lastMsg || msgTime > lastMsg.timestamp;
          });

          newMessages.forEach(msg => {
            this.addChatMessage(msg.user ? msg.user.name : (msg.username || 'Guest'), msg.message);
            this.chatMessages.push({
              id: msg.id,
              username: msg.user ? msg.user.name : (msg.username || 'Guest'),
              message: msg.message,
              timestamp: new Date(msg.created_at).getTime()
            });
          });
        }
      } catch (e) {
        console.error('Chat polling error:', e);
      }
    }, this.CHAT_POLL_INTERVAL_MS);
  }

  /**
   * Send chat message
   */
  async sendChatMessage() {
    const input = document.getElementById('chat-input');
    if (!input || !input.value.trim()) return;

    const message = input.value.trim();
    input.value = ''; // Clear locally immediately

    // this.addChatMessage('You', message); // Optimistic update

    try {
      await $.post(`/api/live-streams/${this.currentStream.id}/messages`, {
        message
      });
    } catch (e) {
      console.error('Error sending message:', e);
    }
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
    }
  } catch (error) {
    console.error('Failed to fetch live streams:', error);
  }
}

// Export for global access
window.agoraClient = agoraClient;
window.liveStreamUI = liveStreamUI;
