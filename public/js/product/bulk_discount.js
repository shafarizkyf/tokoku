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
                        item.cheapest_variation.stock
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
    $('#form-discount').on('submit', function (e) {
        e.preventDefault();

        if (selectedIds.size === 0) {
            alert('Pilih setidaknya satu produk untuk didiskon.');
            return;
        }

        const type = $('#discount_type').val();
        const value = $('#discount_value').val();

        if (!value) {
            alert('Masukkan nilai diskon.');
            return;
        }

        if (confirm(`Anda akan menerapkan diskon ${type === 'percentage' ? value + '%' : 'Rp ' + value} ke ${selectedIds.size} produk. Lanjutkan?`)) {
            $.ajax({
                url: '/api/products/bulk-discount',
                method: 'POST',
                data: {
                    product_ids: Array.from(selectedIds),
                    discount_type: type,
                    discount_value: value
                },
                success: function (response) {
                    alert(response.message);
                    table.ajax.reload();
                    selectedIds.clear();
                    $('#selected-count').text('0 produk dipilih');
                    $('#check-all').prop('checked', false);
                },
                error: function (xhr) {
                    alert(xhr.responseJSON.message || 'Terjadi kesalahan');
                }
            });
        }
    });
});
