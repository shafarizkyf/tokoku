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

  async init() {
    try {
      this.client = await createAgoraClient('audience');
      this.registerEventHandlers();
      console.log('Agora client initialized successfully');
      return true;
    } catch (error) {
      console.error('Failed to initialize Agora client:', error);
      return false;
    }
  }

  registerEventHandlers() {
    this.client.on('user-published', async (user, mediaType) => {
      await this.client.subscribe(user, mediaType);

      if (mediaType === 'video') {
        const remoteVideoTrack = user.videoTrack;
        const playerContainer = document.getElementById('live-player');
        playerContainer.innerHTML = '';
        remoteVideoTrack.play(playerContainer);
        this.updateViewerCount();
      }

      if (mediaType === 'audio') {
        const remoteAudioTrack = user.audioTrack;
        remoteAudioTrack.play();
      }

      this.remoteUsers[user.uid] = user;
    });

    this.client.on('user-unpublished', (user, mediaType) => {
      if (mediaType === 'video') {
        const playerContainer = document.getElementById('live-player');
        playerContainer.innerHTML = '<div class="text-white text-center p-5">Stream ended</div>';
      }
      delete this.remoteUsers[user.uid];
      this.updateViewerCount();
    });

    this.client.on('user-left', (user) => {
      delete this.remoteUsers[user.uid];
      this.updateViewerCount();
    });

    this.client.on('connection-state-change', (curState, prevState) => {
      console.log(`Connection state changed from ${prevState} to ${curState}`);
    });
  }

  async join(channelName, token = null, uid = null) {
    try {
      if (!this.client) {
        await this.init();
      }
      await this.client.join(this.appId, channelName, token, uid);
      this.isJoined = true;
      console.log('Joined channel successfully:', channelName);
      return true;
    } catch (error) {
      console.error('Failed to join channel:', error);
      return false;
    }
  }

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

  updateViewerCount() {
    const viewerCountEl = document.querySelector('.viewer-count-number');
    if (viewerCountEl) {
      const count = Object.keys(this.remoteUsers).length + 1;
      viewerCountEl.textContent = count;
    }
  }

  async getChannelStats() {
    if (this.client && this.isJoined) {
      return await this.client.getRTCStats();
    }
    return null;
  }
}

class LiveStreamUI {
  constructor(agoraClient) {
    this.agoraClient = agoraClient;
    this.currentStream = null;
  }

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
            <button class="btn-watch" onclick="liveStreamUI.openModal()">Watch Now</button>
            <button class="btn-close" onclick="liveStreamUI.hideFloatingCard()">✕</button>
          </div>
        </div>
      </div>
    `;
    container.style.display = 'block';
  }

  hideFloatingCard() {
    const container = document.getElementById('live-stream-container');
    if (container) {
      container.style.display = 'none';
    }
  }

  async openModal() {
    if (!this.currentStream) return;

    const modal = document.getElementById('live-stream-modal');
    if (!modal) return;

    const modalTitle = modal.querySelector('.live-modal-title');
    if (modalTitle) {
      modalTitle.innerHTML = `
        <span class="live-dot"></span>
        ${this.currentStream.title}
      `;
    }

    modal.classList.add('active');

    let token = null;
    try {
      const joinResponse = await fetch(`/api/live-streams/${this.currentStream.id}/join`, {
        method: 'POST',
        body: JSON.stringify({})
      });
      if (joinResponse.ok) {
        const joinData = await joinResponse.json();
        token = joinData?.data?.agora?.token || null;
        const streamData = joinData?.data?.stream || {};
        this.currentStream.token = token;
        this.currentStream.session_id = joinData?.data?.session_id || null;

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

    const success = await this.agoraClient.join(
      this.currentStream.channelName,
      token,
      null
    );

    if (success) {
      console.log('Successfully joined live stream');
      this.connectChat();
    } else {
      alert('Failed to join live stream. Please try again.');
      this.closeModal();
    }
  }

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
          <button class="btn-buy-now" data-product-id="${product.id}" onclick="liveStreamUI.addToCart(${product.id}, ${product.cheapest_variation.id}, this)">Masukan ke keranjang</button>
        </div>
      `;
      listContainer.appendChild(el);
    });
  }

  async addToCart(productId, productVariationId, btnElement) {
    try {
      const response = await $.post('/api/carts', {
        product_id: productId,
        product_variation_id: productVariationId,
        quantity: 1
      });

      if (response.success) {
        const originalText = btnElement.innerText;
        btnElement.innerText = 'Sip udah!';
        btnElement.style.background = '#48bb78';
        setTimeout(() => {
          btnElement.innerText = originalText;
          btnElement.style.background = '';
        }, 2000);
      }
    } catch (e) {
      console.error('Add to cart error:', e);
      alert('Error adding to cart. Please try again.');
    }
  }

  async closeModal() {
    const modal = document.getElementById('live-stream-modal');
    if (modal) {
      modal.classList.remove('active');
    }

    this.disconnectChat();

    try {
      await $.post(`/api/live-streams/${this.currentStream.id}/leave`, {
        session_id: this.currentStream.session_id
      });
    } catch (e) {
      console.error('Error leaving stream:', e);
    }

    await this.agoraClient.leave();

    const playerContainer = document.getElementById('live-player');
    if (playerContainer) {
      playerContainer.innerHTML = '';
    }

    const chatContainer = document.querySelector('.chat-messages');
    if (chatContainer) {
      chatContainer.innerHTML = '';
    }
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

  async sendChatMessage() {
    const input = document.getElementById('chat-input');
    if (!input || !input.value.trim()) return;

    const message = input.value.trim();
    input.value = '';

    await liveStreamChat.sendMessage(message);
  }
}

let agoraClient;
let liveStreamUI;

document.addEventListener('DOMContentLoaded', async function () {
  const appId = document.querySelector('meta[name="agora-app-id"]')?.content;

  if (!appId) {
    console.warn('Agora App ID not found. Live streaming will not work.');
    return;
  }

  agoraClient = new AgoraLiveStream(appId);
  liveStreamUI = new LiveStreamUI(agoraClient);

  const chatInput = document.getElementById('chat-input');
  if (chatInput) {
    chatInput.addEventListener('keypress', function (e) {
      if (e.key === 'Enter') {
        liveStreamUI.sendChatMessage();
      }
    });
  }

  fetchActiveLiveStreams();
});

async function fetchActiveLiveStreams() {
  try {
    const response = await fetch('/api/live-streams/active');

    if (response.ok) {
      const streams = await response.json();

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
            token: null
          });
        }
      }
    }
  } catch (error) {
    console.error('Failed to fetch live streams:', error);
  }
}

window.agoraClient = agoraClient;
window.liveStreamUI = liveStreamUI;
