const currencyFormat = new Intl.NumberFormat('id-ID', {
  style: 'currency',
  currency: 'IDR',
  minimumFractionDigits: 0,
  maximumFractionDigits: 0,
});

// mimic jQuery $(element).on(event, selector, handler)
const on = (element, type, selector, handler) => {
  element.addEventListener(type, (event) => {
    if (event.target.closest(selector)) {
      handler(event);
    }
  });
};

$.ajaxSetup({
  beforeSend: function (xhr) {
    // xhr.setRequestHeader('Authorization', `Bearer ${token}`);
  },
  success: function (x, status, error) {
  },
  error: function (x, status, error) {
    let message = 'Unexpected Error';
    if (x.status === 401) {
      message = 'You don\'t have permission for this action';
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
      alert(message, 'error');
    }
  }
});

const refreshCartCounter = () => {
  $.getJSON('/api/carts/count').then(response => {
    $('#cart-counter').text(response.items_count > 99 ? '99+' : response.items_count);
  });
}

$(function(){
  refreshCartCounter();
});