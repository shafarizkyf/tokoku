$(function(){
  let cartItems = [];
  let deliveryOptions = [];
  let paymentChannels = [];
  let selectedDelivery = null;

  const sessionId = $('input[name="session_id"]').val();
  const publicToken = $('input[name="public_token"]').val();

  const selectizeConfig = {
    valueField: 'id',
    labelField: 'name',
    searchField: 'name',
  };

  const proviceSelectEl = $('#province_id').selectize(selectizeConfig);
  const regencySelectEl = $('#regency_id').selectize(selectizeConfig);
  const districtSelectEl = $('#district_id').selectize(selectizeConfig);
  const villageSelectEl = $('#village_id').selectize(selectizeConfig);
  const paymentMethodSelectEl = $('#payment_method').selectize(selectizeConfig);

  const appendOptions = (selectizeEl, options, initValue = null) => {
    const control = selectizeEl[0].selectize;
    options.forEach(item => {
      control.addOption(item);
    });
    if (initValue) {
      control.setValue([initValue]);
    }
  }

  const currencyFormat = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  });

  const getDeliveryOptions = async (postalCode) => {
    return await $.post(`/api/checkout/${publicToken}/shipping/calculate`, {
      postal_code: postalCode
    });
  }

  const getPaymentChannels = async () => {
    paymentChannels = await $.getJSON(`/api/checkout/${publicToken}/payment-channels`);
    const remapChannels = paymentChannels.map(item => ({ id: item.code, name: item.name }));
    appendOptions(paymentMethodSelectEl, remapChannels);
  }

  const getProvinces = async () => {
    return await $.getJSON(`/api/checkout/${publicToken}/regions/provinces`);
  }

  const getRegencies = async (provinceId) => {
    return await $.getJSON(`/api/checkout/${publicToken}/regions/provinces/${provinceId}/regencies`);
  }

  const getDistricts = async (provinceId, regencyId) => {
    return await $.getJSON(`/api/checkout/${publicToken}/regions/provinces/${provinceId}/regencies/${regencyId}/districts`);
  }

  const getVillages = async (provinceId, regencyId, districtId) => {
    return await $.getJSON(`/api/checkout/${publicToken}/regions/provinces/${provinceId}/regencies/${regencyId}/districts/${districtId}/villages`);
  }

  const getPostalCode = async (villageId) => {
    return await $.getJSON(`/api/checkout/${publicToken}/regions/postal-code/${villageId}`);
  }

  const getPreferredPayment = () => {
    const channelId = $('#payment_method').val();
    return paymentChannels.find(item => item.code === channelId);
  }

  const getCostOfItems = () => {
    return cartItems.reduce((a, b) => a + b.quantity * (b.price_discount || b.price), 0);
  }

  const getCostOfShipping = () => {
    return selectedDelivery ? selectedDelivery.shipping_cost : 0;
  }

  const getCostOfProcessing = () => {
    const preferredPayment = getPreferredPayment();
    let totalFee = 0;
    if (preferredPayment) {
      const { flat, percent } = preferredPayment.total_fee;
      totalFee = flat;
      if (Number(percent)) {
        const feeAmount = (getCostOfItems() + getCostOfShipping()) * Number(percent) / 100;
        totalFee += feeAmount;
      }
    }
    return totalFee;
  }

  const getGrandTotal = () => {
    return getCostOfItems() + getCostOfShipping() - (window.discount || 0) + getCostOfProcessing();
  }

  const updateSummary = () => {
    $('#summary-subtotal').text(currencyFormat.format(getCostOfItems()));
    $('#summary-shipping').text(currencyFormat.format(getCostOfShipping()));
    $('#summary-discount').text('- ' + currencyFormat.format(window.discount || 0));
    $('#summary-fee').text(currencyFormat.format(getCostOfProcessing()));
    $('#summary-total').text(currencyFormat.format(getGrandTotal()));
  }

  const updateDeliveryOptions = async () => {
    $('#shipping-options').empty();
    const postalCode = $('#postal_code').val();

    if (!postalCode) {
      $('#shipping-options').html('<div class="alert alert-info">Pilih alamat lengkap terlebih dahulu</div>');
      return;
    }

    if (!cartItems.length) {
      $('#shipping-options').html('<div class="alert alert-warning">Keranjang checkout kosong</div>');
      return;
    }

    deliveryOptions = await getDeliveryOptions(postalCode);

    if (!deliveryOptions || deliveryOptions.length === 0) {
      $('#shipping-options').html('<div class="alert alert-warning">Opsi pengiriman tidak tersedia untuk alamat ini</div>');
      return;
    }

    deliveryOptions.forEach((item, index) => {
      const isSelected = window.checkoutData && window.checkoutData.serviceType === item.service_name &&
                         window.checkoutData.courier === item.shipping_name;
      const cardEl = $(`
        <div class="card delivery-option cursor-pointer ${isSelected ? 'border-primary' : ''}" data-index="${index}">
          <div class="card-body py-2">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="fw-bold">${item.shipping_name} - ${item.service_name}</div>
                <div class="text-muted small">Estimasi: ${item.etd}</div>
              </div>
              <div class="fw-bold">${currencyFormat.format(item.shipping_cost)}</div>
            </div>
          </div>
        </div>
      `);

      $('#shipping-options').append(cardEl);

      if (isSelected || (!selectedDelivery && index === 0)) {
        selectedDelivery = item;
        cardEl.addClass('border-primary');
      }
    });

    updateSummary();
  }

  const loadCartItems = () => {
    $.getJSON(`/api/checkout/items`, { session_id: sessionId })
      .then(response => {
        if (!response.success || !response.items.length) {
          $('#cart-items').html('<div class="alert alert-warning">Keranjang checkout kosong</div>');
          return;
        }

        cartItems = response.items;

        const itemsHtml = cartItems.map(item => `
          <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
              ${item.product_image ? `<img src="${item.product_image.url}" alt="${item.product_name}" style="width: 50px; height: 50px; object-fit: cover;" class="rounded">` : ''}
              <div>
                <div class="fw-medium">${item.product_name}</div>
                <div class="text-muted small">
                  ${currencyFormat.format(item.price_discount || item.price)} x ${item.quantity}
                </div>
              </div>
            </div>
            <div class="fw-medium">${currencyFormat.format(item.subtotal)}</div>
          </div>
        `).join('');

        $('#cart-items').html(itemsHtml);
        updateSummary();
      })
      .catch(() => {
        $('#cart-items').html('<div class="alert alert-danger">Gagal memuat keranjang</div>');
      });
  }

  $('#payment_method').on('change', function() {
    const payment = getPreferredPayment();
    if (payment) {
      const feeInfo = payment.total_fee;
      let infoText = `Biaya: Rp ${feeInfo.flat.toLocaleString('id-ID')}`;
      if (feeInfo.percent) {
        infoText += ` + ${feeInfo.percent}%`;
      }
      $('#payment-info').text(infoText).removeClass('d-none');
    } else {
      $('#payment_info').addClass('d-none');
    }
    updateSummary();
  });

  $(document).on('click', '.delivery-option', function() {
    $('.delivery-option').removeClass('border-primary');
    $(this).addClass('border-primary');
    const index = $(this).data('index');
    selectedDelivery = deliveryOptions[index];
    updateSummary();
  });

  $('#province_id').on('change', function(){
    const provinceId = $(this).val();
    regencySelectEl[0].selectize.clearOptions();
    districtSelectEl[0].selectize.clearOptions();
    villageSelectEl[0].selectize.clearOptions();
    $('#postal_code').val('');

    if (provinceId) {
      getRegencies(provinceId).then(response => {
        appendOptions(regencySelectEl, response);
      });
    }
  });

  $('#regency_id').on('change', function(){
    const provinceId = $('#province_id').val();
    const regencyId = $(this).val();
    districtSelectEl[0].selectize.clearOptions();
    villageSelectEl[0].selectize.clearOptions();
    $('#postal_code').val('');

    if (provinceId && regencyId) {
      getDistricts(provinceId, regencyId).then(response => {
        appendOptions(districtSelectEl, response);
      });
    }
  });

  $('#district_id').on('change', function(){
    const provinceId = $('#province_id').val();
    const regencyId = $('#regency_id').val();
    const districtId = $(this).val();
    villageSelectEl[0].selectize.clearOptions();
    $('#postal_code').val('');

    if (provinceId && regencyId && districtId) {
      getVillages(provinceId, regencyId, districtId).then(response => {
        appendOptions(villageSelectEl, response);
      });
    }
  });

  $('#village_id').on('change', function(){
    const villageId = $(this).val();
    $('#postal_code').val('');

    if (villageId) {
      getPostalCode(villageId).then(response => {
        if (response && response.length > 0) {
          $('#postal_code').val(response[0][0]).trigger('change');
        }
      });
    }
  });

  $('#postal_code').on('change', function() {
    if ($(this).val() && cartItems.length) {
      updateDeliveryOptions();
    }
  });

  $('#checkout-form').on('submit', function(e) {
    e.preventDefault();

    if (!selectedDelivery) {
      toast({ text: 'Pilih metode pengiriman terlebih dahulu', type: 'error' });
      return;
    }

    const payment = getPreferredPayment();
    if (!payment) {
      toast({ text: 'Pilih metode pembayaran terlebih dahulu', type: 'error' });
      return;
    }

    const formData = {
      session_id: sessionId,
      recipient_name: $('#recipient_name').val(),
      recipient_email: $('#recipient_email').val(),
      recipient_phone: $('#recipient_phone').val(),
      address_detail: $('#address_detail').val(),
      province_id: $('#province_id').val(),
      regency_id: $('#regency_id').val(),
      district_id: $('#district_id').val(),
      village_id: $('#village_id').val(),
      postal_code: $('#postal_code').val(),
      note: $('#note').val(),
      courier: selectedDelivery.shipping_name,
      service_type: selectedDelivery.service_name,
      shipping_price: selectedDelivery.shipping_cost,
      payment_method: $('#payment_method').val(),
    };

    $('#btn-pay').text('Memproses...').prop('disabled', true);

    $.post('/api/checkout/process', formData)
      .then(response => {
        if (response.success && response.payment_url) {
          window.location.href = response.payment_url;
        } else if (response.success) {
          window.location.href = response.redirect_url || '/orders';
        } else {
          toast({ text: response.message || 'Terjadi kesalahan', type: 'error' });
        }
      })
      .catch(error => {
        const message = error.responseJSON?.message || 'Terjadi kesalahan saat memproses checkout';
        toast({ text: message, type: 'error' });
      })
      .always(() => {
        $('#btn-pay').text('Bayar Sekarang').prop('disabled', false);
      });
  });

  const fillExistingData = () => {
    if (!window.checkoutData || !window.checkoutData.hasPaymentUrl) {
      return;
    }

    const data = window.checkoutData;

    $('#recipient_name').val(data.recipientName);
    $('#recipient_email').val(data.recipientEmail);
    $('#recipient_phone').val(data.recipientPhone);
    $('#address_detail').val(data.addressDetail);
    $('#note').val(data.note);
    $('#postal_code').val(data.postalCode);

    $('input').attr('disabled', 'disabled');
    $('textarea').attr('disabled', 'disabled');

    $('#btn-pay').text('Lanjutkan ke Pembayaran');
    $('#btn-pay').off('click').on('click', function(e) {
      e.preventDefault();
      if (data.paymentUrl) {
        window.location.href = data.paymentUrl;
      }
    });
  };

  getProvinces().then(provinces => {
    appendOptions(proviceSelectEl, provinces);
  });

  loadCartItems();
  getPaymentChannels().then(() => {
    fillExistingData();
  });
});
