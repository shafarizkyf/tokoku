$(function () {
    let selectedIds = new Set();

    const table = $('#table-products-select').DataTable({
        serverSide: true,
        ajax: {
            url: '/api/products?view=datatable',
            dataSrc: function (items) {
                return items.data.map(item => {
                    const isChecked = selectedIds.has(item.id) ? 'checked' : '';
                    return [
                        `<input type="checkbox" class="product-check" value="${item.id}" ${isChecked}>`,
                        `<div class="d-flex gap-3">
                        <img src="${item.image?.url || ''}" alt="" width="50" />
                        <div>
                            <div class="fw-bold">${item.name}</div>
                            <small class="text-muted">${item.slug}</small>
                        </div>
                    </div>`,
                        item.cheapest_variation.discount_price
                            ? `<span>${currencyFormat.format(item.cheapest_variation.discount_price)}</span>
                                <br>
                                <span class="text-muted text-decoration-line-through">${currencyFormat.format(item.cheapest_variation.price)}</span>`
                            : currencyFormat.format(item.cheapest_variation.price),
                        `<span class="badge ${item.cheapest_variation.stock > 0 ? 'bg-success' : 'bg-danger'}">${item.cheapest_variation.stock}</span>`
                    ];
                });
            }
        },
        columnDefs: [
            { orderable: false, targets: 0 }
        ]
    });

    // Handle check all
    $('#check-all').on('change', function () {
        const isChecked = this.checked;
        $('.product-check').prop('checked', isChecked).trigger('change');
    });

    // Handle individual check (delegated)
    $('#table-products-select tbody').on('change', '.product-check', function () {
        const id = parseInt($(this).val());
        if (this.checked) {
            selectedIds.add(id);
        } else {
            selectedIds.delete(id);
        }
        $('#selected-count').text(`${selectedIds.size} produk dipilih`);
    });

    // Handle form submit
    $('#form-stock').on('submit', function (e) {
        e.preventDefault();

        if (selectedIds.size === 0) {
            toast({
                text: 'Pilih setidaknya satu produk untuk diperbarui stoknya.'
            });
            return;
        }

        const action = $('#stock_action').val();
        const value = $('#stock_value').val();

        if (!value || value < 0) {
            toast({
                text: 'Masukkan nilai stok yang valid.'
            });
            return;
        }

        let actionText = '';
        if (action === 'set') {
            actionText = `mengatur stok menjadi ${value}`;
        } else if (action === 'add') {
            actionText = `menambah stok sebanyak ${value}`;
        } else if (action === 'subtract') {
            actionText = `mengurangi stok sebanyak ${value}`;
        }

        if (confirm(`Anda akan ${actionText} untuk ${selectedIds.size} produk. Lanjutkan?`)) {
            $.ajax({
                url: '/api/products/bulk-stock',
                method: 'POST',
                data: {
                    product_ids: Array.from(selectedIds),
                    stock_action: action,
                    stock_value: parseInt(value)
                },
                success: function (response) {
                    toast({
                        text: response.message
                    });
                    table.ajax.reload();
                    selectedIds.clear();
                    $('#selected-count').text('0 produk dipilih');
                    $('#check-all').prop('checked', false);
                    $('#stock_value').val('');
                },
                error: function (xhr) {
                    toast({
                        text: xhr.responseJSON?.message || 'Terjadi kesalahan'
                    });
                }
            });
        }
    });
});
