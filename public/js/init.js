const token = $('meta[name="token"]').attr('content');
const userType = $('meta[name="user-type"]').attr('content');

const currencyFormat = new Intl.NumberFormat('id-ID', {
  style: 'currency',
  currency: 'IDR',
  minimumFractionDigits: 0,
  maximumFractionDigits: 0,
});

const LOCAL_KEY = {
  SHIPPING: 'tokoku_shipping'
}

// mimic jQuery $(element).on(event, selector, handler)
const on = (element, type, selector, handler) => {
  element.addEventListener(type, (event) => {
    if (event.target.closest(selector)) {
      handler(event);
    }
  });
};

const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))

$.ajaxSetup({
  beforeSend: function (xhr) {
    xhr.setRequestHeader('Authorization', `Bearer ${token}`);
  },
  success: function (x, status, error) {
  },
  error: function (x, status, error) {
    let message = 'Unexpected Error';
    if (x.status === 401) {
      $('#loginModal').modal('show');
      message = '';
    } else if (x.status === 404) {
      message = 'Data not found';
    } else if (x.status === 403) {
      message = 'Access Forbidden';
    } else if (x.status === 422) {
      const errors = x.responseJSON?.errors;
      message = x.responseJSON?.message || '';
      if (errors) {
        message = '';
        Object.keys(errors).map((key) => {
          message += errors[key].join() + '\n';
        });
      }
    } else if (status === 'abort') {
      message = null;
    } else if (x.responseJSON?.message) {
      message = x.responseJSON.message
    }

    if (message) {
      toast({ text: message });
    }
  }
});

const toast = ({ text, duration = 3000 }) => {
  Toastify({
    text,
    duration,
    gravity: "top", // `top` or `bottom`
    position: "right", // `left`, `center` or `right`
    stopOnFocus: true, // Prevents dismissing of toast on hover
    style: {
      background: "linear-gradient(to right, #535BED, #2DA1CF)",
    },
  }).showToast();
}

const refreshCartCounter = () => {
  if (!token) return;

  $.getJSON('/api/carts/count').then(response => {
    $('#cart-counter').text(response.items_count > 99 ? '99+' : response.items_count);
  });
}

const getUrlQuery = (key) => {
  const url = new URL(location.href);
  return url.searchParams.get(key);
}

/**
 * Shared Live Stream Chat Manager using Ably
 */
class LiveStreamChat {
  constructor() {
    this.ably = null;
    this.ablyChannel = null;
    this.isAblyConnected = false;
    this.chatMessages = [];
    this.currentStream = null;
    this.onMessageCallback = null;
    this.onSystemCallback = null;
  }

  setCurrentStream(streamId) {
    this.currentStream = streamId;
  }

  async initAbly() {
    if (typeof Ably === 'undefined') {
      console.error('Ably library not loaded');
      return false;
    }

    if (this.ably && this.isAblyConnected) {
      return true;
    }

    try {
      this.ably = new Ably.Realtime({
        authUrl: `/api/live-streams/ably/token?live_stream_id=${this.currentStream}`,
        queryTime: true
      });

      this.ably.connection.on('connected', () => {
        console.log('Ably connected successfully');
        this.isAblyConnected = true;
      });

      this.ably.connection.on('disconnected', () => {
        console.log('Ably disconnected');
        this.isAblyConnected = false;
      });

      this.ably.connection.on('failed', (err) => {
        console.error('Ably connection failed:', err);
        this.isAblyConnected = false;
      });

      return new Promise((resolve) => {
        const timeout = setTimeout(() => {
          resolve(this.ably.connection.state === 'connected');
        }, 5000);

        if (this.ably.connection.state === 'connected') {
          clearTimeout(timeout);
          this.isAblyConnected = true;
          resolve(true);
        }
      });
    } catch (e) {
      console.error('Failed to initialize Ably:', e);
      return false;
    }
  }

  async connect() {
    if (!this.currentStream) return;

    await this.initAbly();

    const channelName = `live-stream:${this.currentStream}`;
    this.ablyChannel = this.ably.channels.get(channelName);

    this.ablyChannel.subscribe('message', (message) => {
      this.chatMessages.push({
        id: message.data.id,
        username: message.data.username,
        message: message.data.message,
        timestamp: new Date(message.data.created_at).getTime()
      });
      if (this.onMessageCallback) {
        this.onMessageCallback(message.data.username, message.data.message);
      }
    });

    this.ablyChannel.subscribe('system', (message) => {
      if (this.onSystemCallback) {
        this.onSystemCallback(message.data.message);
      }
    });

    console.log('Subscribed to chat channel:', channelName);
  }

  disconnect() {
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

    this.chatMessages = [];
  }

  async sendMessage(message) {
    try {
      await $.post(`/api/live-streams/${this.currentStream}/messages`, { message });
      return true;
    } catch (e) {
      console.error('Error sending message:', e);
      return false;
    }
  }

  onMessage(callback) {
    this.onMessageCallback = callback;
  }

  onSystem(callback) {
    this.onSystemCallback = callback;
  }
}

/**
 * Create shared Agora client
 */
async function createAgoraClient(role = 'audience') {
  const appId = document.querySelector('meta[name="agora-app-id"]')?.content;
  if (!appId) {
    console.error('Agora App ID not found');
    return null;
  }

  const client = AgoraRTC.createClient({ mode: 'live', codec: 'vp8' });
  await client.setClientRole(role);
  return client;
}

const liveStreamChat = new LiveStreamChat();

$(function(){
  refreshCartCounter();

  $('input[type="search"]').on('keyup', _.debounce(function(){
    const keyword = $(this).val();
    if (!keyword.trim().length) {
      $('.search-result').addClass('d-none');
      $('.search-result').empty();
      return;
    }

    const container = $('.search-result').empty();

    // container.append(`
    //   <li>
    //     <a href="/?q=${encodeURIComponent(keyword)}">Lihat semua pencarian untuk <span class="fw-medium">${keyword}</span></a>
    //   </li>
    // `);

    $.getJSON(`/api/search?keyword=${keyword}`).then(response => {
      $('.search-result').removeClass('d-none');

      if (response.length) {
        const list = response.map(item => `<li><a href="/products/${item.slug}">${item.name}</a></li>`).join('');
        container.append( `<li><a href="/search?q=${keyword}">Lihat Semua</a></li>`).append(list);
      } else {
        container.empty().append(`<li>Pencarian tidak ditemukan</li>`);
      }
    })
  }, 400));
});