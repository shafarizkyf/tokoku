const ImportProductCardEl = ({ imageUrl, title, price, viewUrl, index }) => {
  return `
    <div class="card" style="width: 200px;">
      <img src="${imageUrl}" class="card-img-top" alt="${title}">
      <div class="card-body">
        <h5 class="card-title text-ellipsis">${title}</h5>
        <p class="card-text">${price}</p>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="" id="item-${index}" checked>
          <label class="form-check-label" for="item-${index}">
            Selected
          </label>
        </div>
      </div>
    </div>
  `
}