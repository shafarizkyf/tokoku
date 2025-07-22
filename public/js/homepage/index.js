$(function(){

  const productListEl = document.querySelector('.product-list');

  const infiniteScroll = new InfiniteScroll(productListEl, {
    path: function () {
      return `/api/products?page=${this.pageIndex}`;
    },
    responseBody: 'json',
    history: false,
    checkLastPage: true
  });

  infiniteScroll.on('load', function (response) {
    const productsEl = response.data.map((product, index) => {
      return ImportProductCardEl({
        imageUrl: product.image?.url || '#',
        discountPrice: 0,
        normalPrice: 0,
        title: product.name,
        viewUrl: '#'
      })
    });

    console.log(productsEl);
    $('.product-list').append(productsEl.join(''));

    // 👇 Stop fetching more if last page is reached
    if (response.current_page >= response.last_page) {
      infScroll.off('load');         // stops listening to future load events
      infScroll.destroy();           // optional: clean up observers
      console.log("Reached last page.");
    }
  });

  // initial load
  infiniteScroll.loadNextPage();
});