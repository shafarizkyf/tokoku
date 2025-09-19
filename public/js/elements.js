const ProductCardEl = ({ imageUrl, title, normalPrice, discountPrice, viewUrl, children = '', target = '_self' }) => {
  return `
    <a href="${viewUrl}" target="${target}" class="product text-decoration-none card" style="width: calc(100% / 5 - 1rem);">
      <img src="${imageUrl}" class="card-img-top" alt="${title}">
      <div class="card-body">
        <h5 class="card-title text-ellipsis">
          <span>${title}</span>
        </h5>
        <div class="d-flex align-items-center gap-1 mb-3">
          ${discountPrice
              ? `<p class="badge text-bg-danger m-0">${discountPrice}</p> <p class="fs-6 m-0 text-muted text-decoration-line-through">${normalPrice}</p>`
              : `<p class="m-0">${normalPrice}</p>`}
        </div>
        ${children}
      </div>
    </a>
  `
}

const ImportProductCardEl = ({ imageUrl, title, normalPrice, discountPrice, viewUrl, index }) => {
  return ProductCardEl({
    imageUrl,
    title,
    normalPrice,
    discountPrice,
    viewUrl,
    index,
    target: '_blank',
    children: `
      <div class="form-check">
        <input class="form-check-input" type="checkbox" value="" id="item-${index}" checked>
        <label class="form-check-label" for="item-${index}">
          Selected
        </label>
      </div>
    `
  })
}

const ProductVariantOptionEl = ({ index, variant }) => {
  return `
    <div class="row" data-index="${index}">
      <div class="col-md-3">
        <input class="form-control" name="variant-attribute" id="variant-attribute-${index}" value="${variant}" />
      </div>
      <div class="col-md-9">
        <div class="d-flex align-items-center gap-3">
          <select id="attribute-options-${index}" name="variant-options" class="w-100" multiple placeholder=""></select>
          <button type="button" name="btn-remove-variant" class="btn btn-outline-secondary">Hapus</button>
        </div>
      </div>
    </div>
  `
}

const TableRowVariantEditForm = ({ attributeLength, combination }) => {
  const key = combination.join('').replace(/\s/g, '').toLocaleLowerCase();
  return `
    <tr>
      <td>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="1">
        </div>
      </td>
      ${(new Array(attributeLength)).fill('').map((_, i) => `<td data-attribute-index="${i}" scope="row">${combination[i].trim()}</td>`).join('')}
      <td>
        <div class="input-group">
          <span class="input-group-text">Rp</span>
          <input type="text" class="form-control" placeholder="Harga" name="price-${key}" data-key="${key}">
        </div>
      </td>
      <td>
        <input type="text" class="form-control" placeholder="Stok" name="stock-${key}" data-key="${key}">
      </td>
      <td>
        <div class="input-group">
          <input type="text" class="form-control" placeholder="Berat" name="weight-${key}" data-key="${key}">
          <span class="input-group-text">g</span>
        </div>
      </td>
    </tr>
  `
}

const CartItemCard = ({ id, imageUrl, productName, productOptions, price, originalPrice, quantity, subtotal, subtotalOriginal }) => {
  return `
    <div class="card" data-id="${id}">
      <div class="card-body">
        <div class="d-flex gap-3">
          <img src="${imageUrl}" class="img-thumbnail" alt="">
          <div class="w-100">
            <p class="m-0 fs-6 text-ellipsis">${productName}</p>
            <p class="m-0 variation text-muted">${productOptions.join(' - ')}</p>
            <p class="m-0 text-muted">${quantity} x ${currencyFormat.format(originalPrice)}</p>
          </div>
          <div class="">
            <p class="m-0">${currencyFormat.format(subtotal)}</p>
            ${subtotal !== subtotalOriginal ? `<p class="m-0 text-muted text-decoration-line-through">${currencyFormat.format(subtotalOriginal)}</p>` : ''}
          </div>
        </div>
        <div class="d-flex justify-content-end">
          <button class="btn btn-light" name="btn-remove-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
              <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
              <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
            </svg>
          </button>
        </div>
      </div>
    </div>
  `;
}

const DeliveryOptionCard = ({ name, cost, estimation, index }) => {
  return `
    <div class="card delivery-option" role="button" data-index="${index}">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <p class="m-0 fw-medium">${name}</p>
          <p class="m-0 fw-medium">${cost}</p>
        </div>
        <p class="m-0 text-muted fs-6">Estimasi pengiriman: ${estimation}</p>
      </div>
    </div>
  `;
}