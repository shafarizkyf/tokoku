$(function(){
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

  const appendOptions = (selectizeEl, options) => {
    const control = selectizeEl[0].selectize;
    options.forEach(item => {
      control.addOption(item);
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

  $(document).on('click', 'button[name="btn-remove-item"]', function(e){
    e.preventDefault();
    const id = $(this).closest('.card').data('id');
    $.post(`/api/carts/items/${id}`, {
      _method: 'DELETE'
    }).then(response => {
      $(this).closest('.card').remove();
    });
  });

  $('#province_id').on('change', function(){
    const provinceId = $(this).val();
    regencySelectEl[0].selectize.clearOptions();
    districtSelectEl[0].selectize.clearOptions();
    villageSelectEl[0].selectize.clearOptions();
    postalSelectEl[0].selectize.clearOptions();

    if (provinceId) {
      getRegencies(provinceId).then(response => appendOptions(regencySelectEl, response))
    }
  });

  $('#regency_id').on('change', function(){
    const provinceId = $('#province_id').val();
    const regencyId = $(this).val();
    districtSelectEl[0].selectize.clearOptions();
    villageSelectEl[0].selectize.clearOptions();
    postalSelectEl[0].selectize.clearOptions();

    if (provinceId && regencyId) {
      getDistricts(provinceId, regencyId).then(response => appendOptions(districtSelectEl, response))
    }
  });

  $('#district_id').on('change', function(){
    const provinceId = $('#province_id').val();
    const regencyId = $('#regency_id').val();
    const districtId = $('#district_id').val();

    villageSelectEl[0].selectize.clearOptions();
    postalSelectEl[0].selectize.clearOptions();

    if (provinceId && regencyId && districtId) {
      getVillages(provinceId, regencyId, districtId).then(response => appendOptions(villageSelectEl, response))
    }
  });

  $('#village_id').on('change', function(){
    const villageId = $(this).val();
    postalSelectEl[0].selectize.clearOptions();

    if (villageId) {
      getPostalCode(villageId).then(response => {
        const remapOptions = response.map(item => ({id: item[0], name: item[0]}));
        appendOptions(postalSelectEl, remapOptions)
      });
    }
  });


  getProvinces().then(response => {
    appendOptions(proviceSelectEl, response);
  });
});