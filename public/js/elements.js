const ProductCardEl = ({ imageUrl, title, price, viewUrl }) => {
  return `
    <div class="card" style="width: 200px;">
      <img src="${imageUrl}" class="card-img-top" alt="${title}">
      <div class="card-body">
        <h5 class="card-title text-ellipsis">${title}</h5>
        <p class="card-text">${price}</p>
      </div>
    </div>
  `
}