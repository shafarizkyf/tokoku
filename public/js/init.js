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
        container.append(list);
      } else {
        container.empty().append(`<li>Pencarian tidak ditemukan</li>`);
      }
    })
  }, 400));
});