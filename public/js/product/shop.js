$(function(){
  const productId = $('meta[name="product-id"]').attr('content');
  const buttonOptionActiveClass = 'btn-dark';
  const variationOptions = {};
  let variationOption;

  new ImageZoom(document.getElementById('main-img-preview'), {
    // width: 350,
    // height: 350,
    // zoomWidth: 500,
    // fillContainer: true,
    offset: {
      vertical: 0,
      horizontal: 10
    }
  });

  const swiper = new Swiper("#image-slider", {
    slidesPerView: 5,
    spaceBetween: 6,
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev",
    },
  });

  const setActiveButtonOption = (value) => {
    const el = $(`[data-option="${value}"]`);
    const parentEl = el.closest('.attribute');
    // reset button class
    parentEl.find(`[data-option]`).removeClass(buttonOptionActiveClass);
    parentEl.find(`[data-option]`).addClass('btn-outline-dark');
    // mark as active for specific button
    el.removeClass('btn-outline-dark');
    el.addClass(buttonOptionActiveClass);
    // set label
    const activeLabel = parentEl.find(`[data-option="${value}"].${buttonOptionActiveClass}`).text();
    parentEl.find('.selected').text(activeLabel);
  }

  const getAllSelectedOptions = () => {
    const optionIds = [];
    $(`button[data-attribute].${buttonOptionActiveClass}`).each((i, el) => {
      optionIds.push($(el).data('option'));
      $(el).closest('.attribute').addClass('selected');
    });

    return optionIds;
  }

  const toggleAddToCartButtonByStock = (stockAmount = 1) => {
    const button = $('button[name="btn-add-to-cart"]');
    if (stockAmount) {
      button.text('+ Keranjang');
      button.removeAttr('disabled');
    } else {
      button.text('Stok Habis');
      button.attr('disabled', 'disabled');
    }
  }

  const updateSubtotal = () => {
    if (!variationOption) return;

    const quantity = Number($('input[name="quantity"]').val()) || 1;
    const subtotal = (variationOption.discount_price || variationOption.price) * quantity;
    $('#subtotal').text(currencyFormat.format(subtotal));
  }

  // select variant option to fetch price details
  $(document).on('click', 'button[data-attribute]', async function(e){
    e.preventDefault();

    const optionId = $(this).data('option');
    setActiveButtonOption(optionId);

    const optionIds = getAllSelectedOptions();

    // must select all attribute options
    if (optionIds.length !== $('.attribute').length) {
      // set first unselected option as default selected
      $('.attribute:not(.selected)').each((i, el) => {
        $(el).find('button').eq(0).trigger('click');
      });
      return;
    }

    // check if already fetched
    if (variationOptions[optionIds.join(',')]) {
      variationOption = variationOptions[optionIds.join(',')];
    } else {
      const response = await $.getJSON(`/api/products/${productId}/variations?variation_option_id=${optionIds.join(',')}`);

      variationOptions[optionIds.join(',')] = response;
      variationOption = response;
    }

    // remove discount element
    if ($('h2').next().prop('tagName') === 'P') {
      $('h2').next().remove();
    }

    // update price information
    if (variationOption.discount_price) {
      $('h2').text(currencyFormat.format(variationOption.discount_price));
      $('h2').after(`<p class="text-decoration-line-through"><span class="badge bg-danger">${variationOption.discount_percentage}%</span> ${variationOption.price}</p>`)
    } else {
      $('h2').text(currencyFormat.format(variationOption.price));
    }

    $('#stock-amount').text(variationOption.stock);
    $('#weight-amount').text((Number(variationOption.weight) / 1000).toFixed(2));
    $('input[name="quantity"]').attr('max', variationOption.stock);

    toggleAddToCartButtonByStock(variationOption.stock);
    updateSubtotal();
  });

  $('[data-init-options]').data('init-options').forEach(optionId => {
    $(`button[data-option="${optionId}"]`).trigger('click');
  });

  $('button[name="btn-add-to-cart"]').on('click', function(e){
    e.preventDefault();
    const optionIds = getAllSelectedOptions();
    const data = {
      product_id: productId,
      product_variation_id: optionIds.length
        ? variationOptions[optionIds.join(',')]?.id // when product has variant options
        : $('meta[name="init-product-variation"]').attr('content'), // when product does not have variant option
      quantity: $('input[name="quantity"]').val(),
    };

    $.post(`/api/carts`, data).then((response) => {
      refreshCartCounter();
      toast({ text: response.message });
    });
  });

  // handler for changing quantity (by button)
  $(document).on('click', '.quantity > button', function(e){
    e.preventDefault();
    const operation = $(this).attr('name');
    const quantityEl = $('[name="quantity"]');
    const stock = quantityEl.attr('max');

    let quantity = Number(quantityEl.val()) || 1;

    if (operation === 'add') {
      const requestedQuantity = quantity + 1
      if (requestedQuantity <= stock) {
        quantity = requestedQuantity
      } else {
        toast({ text: 'Stok tidak mencukupi' });
      }
    } else if (quantity > 1) {
      quantity -= 1;
    }

    quantityEl.val(quantity);
    updateSubtotal();
  });

  // handler for changing quantity (by text input)
  $(document).on('keyup', 'input[name="quantity"]', function(e) {
    const stock = $('[name="quantity"]').attr('max');
    const requestedQuantity = $(this).val();

    if (requestedQuantity > stock) {
      toast({ text: 'Stok hanya tersedia: ' + stock });
      $(this).val(stock);
    } else if (requestedQuantity < 1) {
      $(this).val('1');
    }

    updateSubtotal();
  });

  // toggle full description
  document.querySelectorAll(".btn-toggle").forEach(btn => {
    btn.addEventListener("click", function () {
      const desc = this.previousElementSibling;
      desc.classList.toggle("expand");

      const text = this.querySelector(".text");
      text.textContent = desc.classList.contains("expand")
        ? "Tutup"
        : "Lihat Selengkapnya";
    });
  });

  // unhide show full description toogle
  const { height: descriptionHeight } = $('.description p')[0].getBoundingClientRect();
  if (descriptionHeight >= 250) {
    $('.description-wrapper button').removeClass('d-none');
  }
});