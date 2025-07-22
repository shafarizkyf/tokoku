$(function(){

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

  const setActiveButtonVariation = (productVariationId) => {
    const el = $(`[data-product-variation="${productVariationId}"]`);
    // reset button class
    $('[data-product-variation]').removeClass('btn-dark');
    $('[data-product-variation]').addClass('btn-outline-dark');
    // mark as active for specific button
    el.removeClass('btn-outline-dark');
    el.addClass('btn-dark');
    // set label
    const activeLabel = $(`[data-product-variation="${productVariationId}"].btn-dark`).text();
    el.closest('.attribute').find('.selected').text(activeLabel);
  }

  $('button[data-product-variation]').on('click', function(e){
    e.preventDefault();
    const productVariationId = $(this).data('product-variation');
    setActiveButtonVariation(productVariationId)
  });

  const initProductVariation = $('[data-init-product-variation]').data('init-product-variation');
  setActiveButtonVariation(initProductVariation)
});