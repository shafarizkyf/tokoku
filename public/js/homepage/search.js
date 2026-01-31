$(function () {
  const productListEl = document.querySelector('.product-list');

  const queryString = window.location.search;
  const urlParams = new URLSearchParams(queryString);

  let currentFilters = {
    keyword: urlParams.get('q') || '',
    sortBy: 'latest',
    condition: ['new', 'used'],
    minPrice: '',
    maxPrice: ''
  };

  let infiniteScroll = null;

  function buildFilterUrl(page = 1) {
    const params = new URLSearchParams();
    params.set('keyword', currentFilters.keyword);
    params.set('sort_by', currentFilters.sortBy);
    params.set('page', page);

    if (currentFilters.condition.length > 0) {
      currentFilters.condition.forEach(c => params.append('condition[]', c));
    }

    if (currentFilters.minPrice) {
      params.set('min_price', currentFilters.minPrice);
    }

    if (currentFilters.maxPrice) {
      params.set('max_price', currentFilters.maxPrice);
    }

    return `/api/products/filter?${params.toString()}`;
  }

  function initInfiniteScroll() {
    if (infiniteScroll) {
      infiniteScroll.destroy();
    }

    productListEl.innerHTML = '';

    infiniteScroll = new InfiniteScroll(productListEl, {
      path: function () {
        return buildFilterUrl(this.pageIndex);
      },
      responseBody: 'json',
      history: false,
      checkLastPage: true
    });

    infiniteScroll.on('load', function (response) {
      const productsEl = response.data.map((product) => {
        return ProductCardEl({
          imageUrl: product.image?.url || '#',
          discountPrice: product.cheapest_variation.discount_price ? currencyFormat.format(product.cheapest_variation.discount_price) : null,
          normalPrice: currencyFormat.format(product.cheapest_variation.price),
          title: product.name,
          viewUrl: `/products/${product.slug}`
        })
      });

      $('.product-list').append(productsEl.join(''));

      if (response.current_page >= response.last_page) {
        infiniteScroll.off('load');
        infiniteScroll.destroy();
      }
    });

    infiniteScroll.loadNextPage();
  }

  function applyFilters() {
    currentFilters.sortBy = $('#sortBy').val();

    currentFilters.condition = [];
    $('input[name="condition"]:checked').each(function () {
      currentFilters.condition.push($(this).val());
    });

    currentFilters.minPrice = $('#minPrice').val();
    currentFilters.maxPrice = $('#maxPrice').val();

    initInfiniteScroll();
  }

  function resetFilters() {
    $('#sortBy').val('latest');
    $('input[name="condition"]').prop('checked', true);
    $('#minPrice').val('');
    $('#maxPrice').val('');

    currentFilters = {
      keyword: urlParams.get('q') || '',
      sortBy: 'latest',
      condition: ['new', 'used'],
      minPrice: '',
      maxPrice: ''
    };

    initInfiniteScroll();
  }

  $('#applyFilters').on('click', function () {
    applyFilters();
  });

  $('#resetFilters').on('click', function () {
    resetFilters();
  });

  $('#minPrice, #maxPrice').on('keypress', function (e) {
    if (e.which === 13) {
      applyFilters();
    }
  });

  $('#sortBy').on('change', function () {
    applyFilters();
  });

  initInfiniteScroll();
});
