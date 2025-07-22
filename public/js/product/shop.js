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

  const setActiveButtonOption = (value) => {
    const el = $(`[data-option="${value}"]`);
    const parentEl = el.closest('.attribute');
    // reset button class
    parentEl.find(`[data-option]`).removeClass('btn-dark');
    parentEl.find(`[data-option]`).addClass('btn-outline-dark');
    // mark as active for specific button
    el.removeClass('btn-outline-dark');
    el.addClass('btn-dark');
    // set label
    const activeLabel = parentEl.find(`[data-option="${value}"].btn-dark`).text();
    parentEl.find('.selected').text(activeLabel);
  }

  $('button[data-option]').on('click', function(e){
    e.preventDefault();
    const optionId = $(this).data('option');
    setActiveButtonOption(optionId);
  });
});