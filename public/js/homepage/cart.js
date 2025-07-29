$(function(){
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
});