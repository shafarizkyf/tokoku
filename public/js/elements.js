const ImportProductCardEl = ({ imageUrl, title, normalPrice, discountPrice, viewUrl, index }) => {
  return `
    <div class="product card" style="width: 200px;">
      <img src="${imageUrl}" class="card-img-top" alt="${title}">
      <div class="card-body">
        <h5 class="card-title text-ellipsis">
          <a href="${viewUrl}" target="_blank">${title}</a>
        </h5>
        <div class="d-flex align-items-center gap-1 mb-3">
          ${discountPrice
              ? `<p class="badge text-bg-danger m-0">${discountPrice}</p> <p class="fs-6 m-0 text-muted text-decoration-line-through">${normalPrice}</p>`
              : `<p class="m-0">${normalPrice}</p>`}
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

const ProductVariantOptionEl = ({ index, attributes }) => {
  return `
    <div class="row" data-index="${index}">
      <div class="col-md-3">
        <select class="form-control" name="variant-attribute" id="variant-attribute-${index}">
          ${attributes.map((option, i) => `<option value="${option.value}" ${i === index ? 'selected' : ''}>${option.text}</option>`).join('')}
        </select>
      </div>
      <div class="col-md-9">
        <div class="d-flex align-items-center gap-3">
          <select id="attribute-options-${index}" class="w-100" multiple placeholder=""></select>
          <button type="button" name="btn-remove-variant" class="btn btn-outline-secondary">Hapus</button>
        </div>
      </div>
    </div>
  `
}