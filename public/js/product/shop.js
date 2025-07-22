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

});