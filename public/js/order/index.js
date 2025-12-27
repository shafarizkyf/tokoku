$(function(){

  let orderId;

  const BAGDE_CLASS = {
    pending: 'text-bg-warning',
    paid: 'text-bg-success',
    shipped: 'text-bg-info',
    cancelled: 'text-bg-danger'
  }

  const table = $('#table-orders').DataTable({
    serverSide: true,
    ordering: false,
    ajax: {
      url: '/api/orders?view=datatable',
      dataSrc: function(items) {
        return items.data.map(item => {
          const orderItems = item.order_details.slice(0, 5).map(item => `
              <a href="/products/${item.product.slug}" target="_blank">
                <img class="tiny" src="${item.product.image.url}" />
              </a>
            `).join('');

          let dropdownMenu = '';
          if (userType === 'admin') {
            dropdownMenu += `<li><button name="btn-show-resi-number" class="dropdown-item" data-id="${item.id}" data-resi-number="${item.resi_number}">Atur Pengiriman</a></li>`;
          }

          if (item.is_cancelable) {
            dropdownMenu += `<li><button name="btn-cancel-confirmation" class="dropdown-item text-danger" data-id="${item.id}">Batalkan Order</button></li>`;
          }

          return [
            item.code,
            item.recipient_name,
            orderItems,
            `<p class="m-0 text-md-end">${currencyFormat.format(item.grand_total)}</p>`,
            `<span class="badge ${BAGDE_CLASS[item.status]}">${item.order_status}</span>`,
            `
              <div class="d-flex justify-content-end">
                <div class="btn-group" role="group">
                  <a href="/orders/${item.code}" class="btn btn-dark btn-sm">Lihat Detail Transaksi</a>
                  ${dropdownMenu
                    ? `
                    <div class="btn-group" role="group">
                      <button type="button" class="btn btn-dark btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"></button>
                      <ul class="dropdown-menu">${dropdownMenu}</ul>
                    </div>`
                    : ''
                  }
                </div>
              </div>
            `
          ]
        });
      }
    },
    columnDefs: [
      {
        width: '5%',
        targets: 0
      },
      {
        width: 140,
        targets: 3
      },
      {
        width: 120,
        targets: 4
      },
      {
        width: '18%',
        targets: 5
      },
    ]
  });

  $(document).on('click', 'button[name="btn-show-resi-number"]', function(e){
    e.preventDefault();
    orderId = $(this).data('id');
    const resiNumber = $(this).data('resi-number');

    $('#resi_number').val(resiNumber);
    $('#resiNumberModal').modal('show');
  });

  $(document).on('click', 'button[name="btn-cancel-confirmation"]', function(e){
    e.preventDefault();
    orderId = $(this).data('id');

    $('#cancelConfirmationModal').modal('show');
  });

  $('button[name="btn-save-resi-number"]').on('click', function(e){
    e.preventDefault();
    const data = {
      _method: 'PATCH',
      resi_number: $('#resi_number').val(),
    }

    $.post(`/api/orders/${orderId}/resi-number`, data).then(response => {
      $('#resiNumberModal').modal('hide');
      toast({ text: response.message });
      table.ajax.reload();
    });
  });

  $('button[name="btn-cancel-order"]').on('click', function(e){
    e.preventDefault();

    const data = {
      _method: 'PATCH',
      resi_number: orderId,
    }

    $.post(`/api/orders/${orderId}/cancel`, data).then(response => {
      $('#cancelConfirmationModal').modal('hide');
      toast({ text: response.message });
      table.ajax.reload();
    });
  });

});