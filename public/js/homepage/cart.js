$(function(){
  $.getJSON('/api/carts').then(response => {
    const cartItemsCard = response.map(item => CartItemCard({
      imageUrl: item.product_image?.url,
      price: item.price,
      productName: item.product_name,
      productOptions: item.options,
    })).join('');

    $('.cart-items').append(cartItemsCard);
  });
});