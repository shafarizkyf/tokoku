$(function () {
  const productListEl = document.querySelector('.product-list');

  const queryString = window.location.search;
  const urlParams = new URLSearchParams(queryString);

  const infiniteScroll = new InfiniteScroll(productListEl, {
    path: function () {
      return `/api/search/all?keyword=${urlParams.get('q')}&page=${this.pageIndex}`;
    },
    responseBody: 'json',
    history: false,
    checkLastPage: true
  });

  infiniteScroll.on('load', function (response) {
    const productsEl = response.data.map((product, index) => {
      return ProductCardEl({
        imageUrl: product.image?.url || '#',
        discountPrice: product.cheapest_variation.discount_price ? currencyFormat.format(product.cheapest_variation.discount_price) : null,
        normalPrice: currencyFormat.format(product.cheapest_variation.price),
        title: product.name,
        viewUrl: `/products/${product.slug}`
      })
    });

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