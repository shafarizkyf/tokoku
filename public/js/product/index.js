$(function(){

  $('#table-products').DataTable({
    serverSide: true,
    ajax: {
      url: '/api/products?view=datatable',
      dataSrc: function(items) {
        return items.data.map(item => {
          return [
            `<div class="d-flex gap-3">
              <img src="${item.image?.url}" alt="" />
              <h5>${item.name}</h5>
            </div>`,
            0,
            0,
            `
              <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                  Pengaturan
                </button>
                <ul class="dropdown-menu">
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
        targets: 3
      }
    ]
  })

});