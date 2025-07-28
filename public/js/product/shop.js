$(function(){
  const buttonOptionActiveClass = 'btn-dark';
  const variationOptions = {};

  new ImageZoom(document.getElementById('main-img-preview'), {
    width: 350,
    height: 350,
    zoomWidth: 500,
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

  // select variant option to fetch price details
  $(document).on('click', 'button[data-attribute]', async function(e){
    e.preventDefault();

    const optionId = $(this).data('option');
    setActiveButtonOption(optionId);

    const optionIds = [];
    $(`button[data-attribute].${buttonOptionActiveClass}`).each((i, el) => {
      optionIds.push($(el).data('option'));
      $(el).closest('.attribute').addClass('selected');
    });

    // must select all attribute options
    if (optionIds.length !== $('.attribute').length) {
      // set first unselected option as default selected
      $('.attribute:not(.selected)').each((i, el) => {
        $(el).find('button').eq(0).trigger('click');
      });
      return;
    }

    let variationOption;
    // check if already fetched
    if (variationOptions[optionIds.join(',')]) {
      variationOption = variationOptions[optionIds.join(',')];
    } else {
      const productId = $('[data-product-id]').data('product-id');
      const response = await $.getJSON(`/api/products/${productId}/variations?variation_option_id=${optionIds.join(',')}`);

      variationOptions[optionIds.join(',')] = response;
      variationOption = response;
    }

    // remove discount element
    if ($('h2').next().prop('tagName') === 'P') {
      $('h2').next().remove();
    }

    if (variationOption.discount_price) {
      $('h2').text(currencyFormat.format(variationOption.discount_price));
      $('h2').after(`<p class="text-decoration-line-through"><span class="badge bg-danger">${variationOption.discount_percentage}%</span> ${variationOption.price}</p>`)
    } else {
      $('h2').text(currencyFormat.format(variationOption.price));
    }
  });

  $('[data-init-options]').data('init-options').forEach(optionId => {
    $(`button[data-option="${optionId}"]`).trigger('click');
  });
});