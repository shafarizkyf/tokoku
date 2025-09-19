$(function(){
  let isEditShippingForm = false;
  let cartItems = [];
  let deliveryOptions = [];
  let paymentChannels = [];

  let currentShippingForm = localStorage.getItem(LOCAL_KEY.SHIPPING)
    ? JSON.parse(localStorage.getItem(LOCAL_KEY.SHIPPING))
    : null

  if (currentShippingForm) {
    $('#selected-address').text(`${currentShippingForm.receiver_name} - ${currentShippingForm.address_full}`);
  }

  const selectizeConfig = {
    valueField: 'id',
    labelField: 'name',
    searchField: 'name',
  };

  const proviceSelectEl = $('#province_id').selectize(selectizeConfig);
  const regencySelectEl = $('#regency_id').selectize(selectizeConfig);
  const districtSelectEl = $('#district_id').selectize(selectizeConfig);
  const villageSelectEl = $('#village_id').selectize(selectizeConfig);
  const postalSelectEl = $('#postal_code').selectize(selectizeConfig);
  const paymentMethodSelectEl = $('#payment_method').selectize(selectizeConfig);

  const appendOptions = (selectizeEl, options, initValue = null) => {
    const control = selectizeEl[0].selectize;
    options.forEach(item => {
      control.addOption(item);
    });

    control.setValue([initValue]);
  }

  const saveShippingForm = () => {
    const address_full = $('#address').val().trim() + ', '
      +  $('#village_id').text().trim() + ', '
      +  $('#district_id').text().trim() + ', '
      +  $('#regency_id').text().trim() + ', '
      +  $('#province_id').text().trim() + ' '
      +  '(' + $('#postal_code').val().trim() + ')'

    const data = {
      receiver_name: $('#receiver_name').val(),
      province_id: $('#province_id').val(),
      regency_id: $('#regency_id').val(),
      district_id: $('#district_id').val(),
      village_id: $('#village_id').val(),
      postal_code: $('#postal_code').val(),
      address: $('#address').val(),
      shipping_note: $('#shipping_note').val(),
      address_full,
    };

    console.log(data);
    localStorage.setItem(LOCAL_KEY.SHIPPING, JSON.stringify(data));
    $('#selected-address').text(`${data.receiver_name} - ${data.address_full}`);
  }

  const getDeliveryOptions = async (postalCode) => {
    return await $.post(`/api/shipping/calculate`, {
      postal_code: postalCode
    });
  }

  const getPaymentChannels = async () => {
    paymentChannels = await $.getJSON(`/api/payments/channels`);
    const remapChannels = paymentChannels.map(item => ({ id: item.code, name: item.name }));
    appendOptions(paymentMethodSelectEl, remapChannels);
  }

  const getPreferredDelivery = () => {
    const selectedDeliveryIndex = $('.card.delivery-option.border-primary').data('index');
    return deliveryOptions[selectedDeliveryIndex]
  }

  const getPreferredPayment = () => {
    const channelId = $('#payment_method').val();
    return paymentChannels.find(item => item.code === channelId);
  }

  const getCostOfItems = () => {
    return cartItems.reduce((a, b) => a + b.price, 0);
  }

  const getCostOfShipping = () => {
    const preferredDelivery = getPreferredDelivery();
    return preferredDelivery ? preferredDelivery['shipping_cost'] : 0;
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
    return getCostOfItems() +
      getCostOfShipping() +
      getCostOfProcessing()
  }

  const updateCostOfItems = () => {
    $('#item-count').text(cartItems.length);
    $('#cost-items').text(currencyFormat.format(getCostOfItems()));
    // need to update because some has % fee
    updateCostOfProcessing();

    updateGrandTotal();
  }

  const updateCostOfShipping = () => {
    $('#cost-shipping').text(currencyFormat.format(getCostOfShipping()));
    // need to update because some has % fee
    updateCostOfProcessing();

    updateGrandTotal();
  }

  const updateCostOfProcessing = () => {
    $('#cost-proccessing').text(currencyFormat.format(getCostOfProcessing()));

    updateGrandTotal();
  }

  const updateGrandTotal = () => {
    $('#grand-total').text(currencyFormat.format(getGrandTotal()));
  }

  const toggleContainerVisibility = (hasItem = false) => {
    if (hasItem) {
      $('h1').removeClass('d-none');
      $('#cart-content').removeClass('d-none');
    } else {
      $('#cart-empty-content').removeClass('d-none');
      $('#cart-content').addClass('d-none');
      $('h1').addClass('d-none');
    }
  }

  $.getJSON('/api/carts').then(response => {
    cartItems = response;

    toggleContainerVisibility(response.length > 0)

    const cartItemsCard = response.map(item => CartItemCard({
      imageUrl: item.product_image?.url,
      price: item.price_discount || item.price,
      originalPrice: item.price,
      productName: item.product_name,
      productOptions: item.options,
      id: item.cart_item_id,
    })).join('');

    $('.cart-items').append(cartItemsCard);

    updateCostOfItems();
  });

  $('#btn-set-shipping').on('click', function(){
    if (!isEditShippingForm) {
      $(this).text('Simpan');
      $('#btn-pay').addClass('d-none');
      $('#selected-address').addClass('d-none');
      $('#delivery-options').addClass('d-none');
      $('#shipping-form').removeClass('d-none');
      isEditShippingForm = true;
    } else {
      $(this).text('Atur alamat');
      saveShippingForm();
      $('#btn-pay').removeClass('d-none');
      $('#selected-address').removeClass('d-none');
      $('#delivery-options').removeClass('d-none');
      $('#shipping-form').addClass('d-none');
      isEditShippingForm = false;
    }
  });

  $(document).on('click', 'button[name="btn-remove-item"]', function(e){
    e.preventDefault();
    const id = $(this).closest('.card').data('id');
    $.post(`/api/carts/items/${id}`, {
      _method: 'DELETE'
    }).then(response => {
      cartItems = cartItems.filter(item => item.cart_item_id !== id);
      toggleContainerVisibility(cartItems.length > 0);

      $(this).closest('.card').remove();
    }).always(() => {
      updateCostOfItems();
      updateGrandTotal();
    });
  });

  $(document).on('click', '.card.delivery-option', function(){
    $('.card.delivery-option').removeClass('border-primary');
    $(this).addClass('border-primary');
    updateCostOfShipping();
  });

  $('#province_id').on('change', function(){
    const provinceId = $(this).val();
    regencySelectEl[0].selectize.clearOptions();
    districtSelectEl[0].selectize.clearOptions();
    villageSelectEl[0].selectize.clearOptions();
    postalSelectEl[0].selectize.clearOptions();

    if (provinceId) {
      getRegencies(provinceId).then(response => {
        appendOptions(regencySelectEl, response, currentShippingForm?.regency_id);
      })
    }
  });

  $('#regency_id').on('change', function(){
    const provinceId = $('#province_id').val();
    const regencyId = $(this).val();
    districtSelectEl[0].selectize.clearOptions();
    villageSelectEl[0].selectize.clearOptions();
    postalSelectEl[0].selectize.clearOptions();

    if (provinceId && regencyId) {
      getDistricts(provinceId, regencyId).then(response => appendOptions(districtSelectEl, response, currentShippingForm?.district_id))
    }
  });

  $('#district_id').on('change', function(){
    const provinceId = $('#province_id').val();
    const regencyId = $('#regency_id').val();
    const districtId = $('#district_id').val();

    villageSelectEl[0].selectize.clearOptions();
    postalSelectEl[0].selectize.clearOptions();

    if (provinceId && regencyId && districtId) {
      getVillages(provinceId, regencyId, districtId).then(response => appendOptions(villageSelectEl, response, currentShippingForm?.village_id))
    }
  });

  $('#village_id').on('change', function(){
    const villageId = $(this).val();
    postalSelectEl[0].selectize.clearOptions();

    if (villageId) {
      getPostalCode(villageId).then(response => {
        const remapOptions = response.map(item => ({id: item[0], name: item[0]}));
        appendOptions(postalSelectEl, remapOptions, currentShippingForm?.postal_code)
      });
    }
  });

  $('#postal_code').on('change', async function(){
    const value = $(this).val();

    // to not fetch delivery options
    if (!value || !cartItems.length) {
      return;
    }

    $('#delivery-options').empty();
    deliveryOptions = await getDeliveryOptions(value);
    deliveryOptions.forEach((item, index) => {
      const cardEl = DeliveryOptionCard({
        name: `${item.shipping_name} - ${item.service_name}`,
        cost: currencyFormat.format(item.shipping_cost),
        estimation: item.etd,
        index
      });

      $('#delivery-options').append(cardEl);
      $('#delivery-options .card').eq(0).trigger('click');
    });
  });

  $('#payment_method').on('change', function(){
    updateCostOfProcessing();
  });

  $('#btn-pay').on('click', function(e){
    e.preventDefault();
    const orderItems = cartItems.map(item => ({
      product_variation_id: item.product_variation_id,
      quantity: item.quantity,
    }));

    const order = {
      items: orderItems,
      payment_method: $('#payment_method').val(),
      shipping: currentShippingForm,
      delivery: getPreferredDelivery(),
    }

    console.info('order', order);

    $(this).text('Mohon tunggu...');
    $(this).attr('disabled', 'disabled');
    $.post('/api/orders', order).then(response => {
      if (response.success && response?.data?.url) {
        location.href = response.data.url;
      }
    }).fail((error) => {
      $(this).text('Bayar');
      $(this).removeAttr('disabled', 'disabled');
      console.error(error);
    })
  });

  getProvinces().then(response => {
    appendOptions(proviceSelectEl, response, currentShippingForm?.province_id);
    $('#receiver_name').val(currentShippingForm?.receiver_name || '');
    $('#address').val(currentShippingForm?.address || '');
    $('#shipping_note').val(currentShippingForm?.shipping_note || '');
  });

  getPaymentChannels();
});