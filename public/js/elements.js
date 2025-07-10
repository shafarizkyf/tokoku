const ImportProductCardEl = ({ imageUrl, title, normalPrice, discountPrice, viewUrl, index }) => {
  return `
    <div class="card" style="width: 200px;">
      <img src="${imageUrl}" class="card-img-top" alt="${title}">
      <div class="card-body">
        <h5 class="card-title text-ellipsis">${title}</h5>
        <div class="d-flex gap-1 mb-3">
          ${discountPrice ? `<p class="badge text-bg-danger m-0">${discountPrice}</p>` : ''}
          <p class="fs-6 m-0 text-muted">${normalPrice}</p>
        </div>
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