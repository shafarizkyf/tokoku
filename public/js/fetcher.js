const getShopInfo = async (domain) => {
  return await $.post('http://localhost:3000/shop-info', {
    domain
  });
}