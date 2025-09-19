$(function(){

  const BAGDE_CLASS = {
    pending: 'text-bg-warning',
    paid: 'text-bg-success',
    shipped: 'text-bg-info',
    cancelled: 'text-bg-danger'
  }

  $('#table-orders').DataTable({
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

          return [
            item.code,
            item.recipient_name,
            orderItems,
            `<p class="m-0 text-md-end">${currencyFormat.format(item.grand_total)}</p>`,
            `<span class="badge ${BAGDE_CLASS[item.status]}">${item.order_status}</span>`,
            `
              <div class="d-flex justify-content-end">
                <div class="btn-group" role="group">
                  <a href="/orders/${item.code}" class="btn btn-primary btn-sm">Lihat Detail Transaksi</a>
                  <div class="btn-group" role="group">
                    <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"></button>
                    <ul class="dropdown-menu">
                      <li><a class="dropdown-item" href="#">Atur Pengiriman</a></li>
                      <li><button class="dropdown-item text-danger" href="#">Batalkan Order</button></li>
                    </ul>
                  </div>
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
  })

});