$(function () {

  $('#table-products').DataTable({
    serverSide: true,
    ajax: {
      url: '/api/products?view=datatable',
      dataSrc: function (items) {
        return items.data.map(item => {
          return [
            `<div class="d-flex gap-3">
              <img src="${item.image?.url}" alt="" />
              <h5>${item.name}</h5>
            </div>`,
            item.cheapest_variation.discount_price
              ? `<span>${currencyFormat.format(item.cheapest_variation.discount_price)}</span>
                    <br>
                    <span class="text-muted text-decoration-line-through">${currencyFormat.format(item.cheapest_variation.price)}</span>`
              : currencyFormat.format(item.cheapest_variation.price),
            item.cheapest_variation.stock,
            `
              <div class="form-check form-switch">
                <input class="form-check-input toggle-active" type="checkbox" role="switch" data-id="${item.id}" ${item.is_active ? 'checked' : ''}>
              </div>
            `,
            `
              <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                  Pengaturan
                </button>
                <ul class="dropdown-menu">
                  <li><a class="dropdown-item" href="/products/${item.slug}" target="_blank">Lihat</a></li>
                  <li><a class="dropdown-item" href="/products/${item.id}/edit">Edit</a></li>
                </ul>
              </div>
            `
          ]
        });
      }
    },
    columnDefs: [
      {
        width: '30%',
        targets: 0
      },
      {
        width: '120px',
        targets: 4
      }
    ]
  })

  $('#table-products').on('change', '.toggle-active', function () {
    const id = $(this).data('id');
    const isActive = $(this).is(':checked');

    $.ajax({
      url: `/api/products/${id}/toggle-active`,
      method: 'PATCH',
      data: {
        is_active: isActive ? 1 : 0
      },
      success: function (res) {
        Toastify({
          text: res.message,
          duration: 3000,
          close: true,
          gravity: "top",
          position: "right",
          backgroundColor: "#4fbe87",
        }).showToast();
      },
      error: function (err) {
        $(this).prop('checked', !isActive); // Revert
        Toastify({
          text: err.responseJSON?.message || "Terjadi kesalahan",
          duration: 3000,
          close: true,
          gravity: "top",
          position: "right",
          backgroundColor: "#dc3545",
        }).showToast();
      }
    });
  });

});