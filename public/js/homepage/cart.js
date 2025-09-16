$(function(){
  let isEditShippingForm = false;

  let currentShippingForm = localStorage.getItem(LOCAL_KEY.SHIPPING)
    ? JSON.parse(localStorage.getItem(LOCAL_KEY.SHIPPING))
    : null

  if (currentShippingForm) {
    $('#selected-address').text(`${currentShippingForm.receiver_name} - ${currentShippingForm.address_full}`);
  }

  const regionSelectizeConfig = {
    valueField: 'id',
    labelField: 'name',
    searchField: 'name',
  };

  const proviceSelectEl = $('#province_id').selectize(regionSelectizeConfig);
  const regencySelectEl = $('#regency_id').selectize(regionSelectizeConfig);
  const districtSelectEl = $('#district_id').selectize(regionSelectizeConfig);
  const villageSelectEl = $('#village_id').selectize(regionSelectizeConfig);
  const postalSelectEl = $('#postal_code').selectize(regionSelectizeConfig);

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

  $.getJSON('/api/carts').then(response => {
    const cartItemsCard = response.map(item => CartItemCard({
      imageUrl: item.product_image?.url,
      price: item.price_discount || item.price,
      originalPrice: item.price,
      productName: item.product_name,
      productOptions: item.options,
      id: item.cart_item_id,
    })).join('');

    $('.cart-items').append(cartItemsCard);
  });

  $('#btn-set-shipping').on('click', function(){
    if (!isEditShippingForm) {
      $(this).text('Simpan');
      $('#btn-pay').addClass('d-none');
      $('#selected-address').addClass('d-none');
      $('#shipping-form').removeClass('d-none');
      isEditShippingForm = true;
    } else {
      $(this).text('Atur alamat');
      saveShippingForm();
      $('#btn-pay').removeClass('d-none');
      $('#selected-address').removeClass('d-none');
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
      $(this).closest('.card').remove();
    });
  });

  $(document).on('click', '.card.delivery-option', function(){
    $('.card.delivery-option').removeClass('border-primary');
    $(this).addClass('border-primary');
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
    if (!value) {
      return;
    }

    $('#delivery-options').empty();
    const deliveryOptions = await getDeliveryOptions(value);
    deliveryOptions.forEach(item => {
      const cardEl = DeliveryOptionCard({
        name: `${item.shipping_name} - ${item.service_name}`,
        cost: item.shipping_cost,
        estimation: item.etd
      });

      $('#delivery-options').append(cardEl);
      $('#delivery-options .card').eq(0).addClass('border-primary')
    });
  });

  getProvinces().then(response => {
    appendOptions(proviceSelectEl, response, currentShippingForm?.province_id);
    $('#receiver_name').val(currentShippingForm?.receiver_name || '');
    $('#address').val(currentShippingForm?.address || '');
    $('#shipping_note').val(currentShippingForm?.shipping_note || '');
  });
});