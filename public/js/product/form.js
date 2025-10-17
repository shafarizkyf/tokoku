$(function(){

  let product;
  let productVariants = [];
  let isEditForm = location.pathname.includes('/edit');
  let imageFiles = [];
  let deletedImageIds = [];

  const getProductDetail = () => {
    const productId = location.pathname.split('/')[2];
    $.getJSON(`/api/products/${productId}`).then(response => {
      console.log(response);

      product = response;
      $('#product-name').val(response.name);
      $('#product-description').val(response.description);
      $(`#condition-${response.condition}`).prop('checked', 'checked');

      if (response.variations.length === 1) {
        $('#product-price').val(Number(response.variations[0].price));
        $('#product-stock').val(response.variations[0].stock);

      } else {
        const optionsGroupByAttribute = _.groupBy(response.variation_options, 'attribute_name');

        // setup varian option form
        Object.keys(optionsGroupByAttribute).forEach((attribute, index) => {
          $('button[name="btn-add-variant"]').trigger('click');
          $(`#variant-attribute-${index}`).val(attribute);

          const select = $('#variant-options select')[index].selectize;
          optionsGroupByAttribute[attribute].forEach(item => {
            select.addOption({
              id: item.option_name,
              title: item.option_name,
            });

            select.addItem(item.option_name);
          });
        });

        appendImageThumbnails(product.images.map(item => item.url), 'local');

        // wait for table rows generation
        _.delay(() => {
          // pre-fill table form
          $('#table-variants tbody tr').each((i, el) => {
            const key = $(el).find('[data-key]').eq(0).data('key');
            const productVariation = response.variations.find((item) => item.sku === key);
            if (productVariation) {
              $(`input[name="price-${key}"]`).val(productVariation.price);
              $(`input[name="stock-${key}"]`).val(productVariation.stock);
              $(`input[name="weight-${key}"]`).val(productVariation.weight);
            }
          });
        }, response.variations.length * 100);
      }
    });
  }

  const generateTableVariantRows = (variantCombinations) => {
    return variantCombinations.map(item => TableRowVariantEditForm({
      attributeLength: variantCombinations.length ? variantCombinations[0].split(' + ').length : 0,
      combination: item.split(' + ')
    })).join('');
  }

  const generateTableHead = (_variantAttributes = []) => {
    const variantAttributes = [];
    if (!variantAttributes.length) {
      $('input[name="variant-attribute"]').each((i, el) => variantAttributes.push($(el).val()));
    }

    const tableAttributeHeadEl = variantAttributes.map(item => `<th scope="col">${item}</th>`).join('');
    const tableVariantHeadEl = `
      <tr>
        <th scope="col"></th>
        ${tableAttributeHeadEl}
        <th scope="col">Harga</th>
        <th scope="col">Stok</th>
        <th scope="col">Berat</th>
      </tr>
    `;

    $('#table-variants thead').empty().append(tableVariantHeadEl);
  }

  const generateTableRowsCombination = () => {
    const variants = [];
    $('#variant-options .row').each((i, el) => {
      const attributeName = $(el).find('input').val();
      const attributeOptions = $(el).find('select[name="variant-options"]').val();

      variants.push({
        name: attributeName,
        options: attributeOptions.map(name => ({ name }))
      });
    });

    const updatedCombinations = getVariantCombinations(variants);
    const updatedRows = generateTableVariantRows(updatedCombinations);

    $('#table-variants tbody').empty().append(updatedRows);
  }

  const appendImageThumbnails = (imageUrls = [], sourceType, clearFirst = false) => {
    const imagesEl = imageUrls.map((imageUrl, index) => ImagePreviewTiny({ imageUrl, index, sourceType }));

    if (clearFirst) {
      $('.images').empty();
    }

    $('.images').append(imagesEl.join(''));
  }

  const toggleParentPriceAndStock = (isVisible) => {
    $('#product-price').closest('.row').toggleClass('d-none', !isVisible);
    $('#product-stock').closest('.row').toggleClass('d-none', !isVisible);
  }

  const onSelectItemRemoved = (value, el) => {
    setTimeout(generateTableRowsCombination, 100);
  }

  const onSelectItemAdded = (value, el) => {
    setTimeout(generateTableRowsCombination, 100);
  }

  const getVariantCombinations = (attributes) => {
    // Step 1: Extract all option names, regardless of status
    const allOptions = attributes.map(attr =>
      attr.options.map(opt => opt.name)
    );

    // Step 2: Generate combinations (Cartesian product)
    const combine = (arr) => {
      return arr.reduce((acc, curr) =>
        acc.flatMap(a => curr.map(b => [...a, b]))
      , [[]]);
    };

    // Step 3: Convert arrays of names into strings
    return combine(allOptions).map(combo => combo.join(' + '));
  }

  const makeSelectize = (el, options = []) => {
    return el.selectize({
      maxItems: null,
      valueField: 'id',
      labelField: 'title',
      searchField: 'title',
      options: options,
      create: true,
      onItemRemove: onSelectItemRemoved,
      onItemAdd: onSelectItemAdded,
    });
  }

  const importer = (jsonFile) => {
    new Importer(jsonFile, (product) => {
      console.log(product);

      $('#product-name').val(product.name);
      $('#product-description').val(product.description);
      $('#product-price').val(product.originalPrice);
      $('#product-stock').val(product.stock);

      productVariants = product.variants;

      // show table variants
      if (productVariants.length) {
        $('#table-variants').parent().removeClass('d-none');
      }

      // parent price and stock section will not visible when has variants
      toggleParentPriceAndStock(!Boolean(product.variants.length));

      // generate variant attributes and options section
      $('#variant-options').empty();
      const variantAttributes = product.variants.map(item => ({ value: item.name, text: item.name }));
      product.variants.forEach((variant, index) => {
        const variantOptionEl = ProductVariantOptionEl({
          index,
          variant: variant.name,
        });

        $('#variant-options').append(variantOptionEl);

        const variantOptions = variant.options.map(option => ({ id: option.name, title: option.name, variant }));
        const selection = makeSelectize($(`#attribute-options-${index}`), variantOptions);

        // select all by option
        selection[0].selectize.setValue(variant.options.map(option => option.name));
      });

      appendImageThumbnails(product.images, 'external');

      generateTableHead(variantAttributes.map(item => item.text));
      generateTableRowsCombination();
    });
  };

  const previewImage = () => {
    const images = imageFiles.map(file => URL.createObjectURL(file));
    $('.image-container > button[data-source="tmp"]').parent().remove();
    appendImageThumbnails(images, 'tmp');
  }

  $('#btn-import').on('click', function(){
    $('#imported-file').click();
  });

  $('#imported-file').on('change', function(e){
    const file = $(this)[0].files[0];
    if (file) {
      importer(file);
    }
  });

  $('button[name="btn-add-variant"]').on('click', function(e){
    e.preventDefault();

    // parent price and stock section will not visible when has variants
    toggleParentPriceAndStock(false);

    // generate new variant attribute and options input
    const rowCount = $('#variant-options .row').length;
    const defaultAttributeName = `Varian ${rowCount + 1}`;
    $('#variant-options').append(ProductVariantOptionEl({
      index: rowCount,
      variant: defaultAttributeName,
    }));

    // make the select input as selectize
    const newVariantRowEl = $(`#variant-options > .row:nth-child(${rowCount + 1})`);
    makeSelectize(newVariantRowEl.find('select'));

    // update product variants data
    if (!productVariants[rowCount]) {
      productVariants[rowCount] = {
        name: defaultAttributeName,
        options: []
      };
    }

    generateTableHead();
    generateTableRowsCombination();

    // show table variants
    $('#table-variants').parent().removeClass('d-none');
  });

  $('button[name="btn-add-image"]').on('click', function(e){
    e.preventDefault();
    $('#file-picker').trigger('click');
  });

  $('#file-picker').on('change', function(e){
    e.preventDefault();
    const files = $(this)[0].files;
    for(let i=0; i<files.length; i++) {
      imageFiles.push(files[0]);
    }
    previewImage();
  });

  $('.images').on('click', 'button[name="btn-remove-img"]', function(e){
    e.preventDefault();
    const source = $(this).data('source');
    const index = $(this).data('index');

    if (source === 'tmp') {
      imageFiles.splice(index, 1);
    } else if (source === 'local') {
      const image = product.images[index];
      deletedImageIds.push(image.id);
    }

    $(this).parent().remove();
  });

  $('#variant-options').on('click', 'button[name="btn-remove-variant"]', function(e){
    e.preventDefault();

    const row = $(this).closest('.row');
    const attributeIndex = row.data('index');
    const attributeName = $(`#variant-attribute-${attributeIndex}`).val();

    $('th').each((i, el) => {
      if ($(el).text() === attributeName) {
        // remove attribute from table header
        $(el).remove();
      }
    });

    row.remove();

    productVariants = productVariants.filter(variant => variant.name !== attributeName);

    const noLongerHasVariant = $('th').length === 4;

    // parent price and stock section will not visible when has variants
    toggleParentPriceAndStock(noLongerHasVariant);

    $('#table-variants').parent().toggleClass('d-none', noLongerHasVariant);
    if (noLongerHasVariant) {
      $('#table-variants tbody').empty();
    } else {
      generateTableRowsCombination();
    }
  });

  $('#variant-options').on('keyup', 'input[name="variant-attribute"]', function(){
    const index = $(this).closest('.row').data('index');

    if (productVariants[index]) {
      productVariants[index].name = $(this).val();
    }

    generateTableHead();
  });

  $('button[name="btn-save"]').on('click', function(e){
    e.preventDefault();

    const variations = [];
    $('#table-variants tbody > tr').each((i, el) => {
      const price = $(el).find('input[placeholder="Harga"]').val();
      const stock = $(el).find('input[placeholder="Stok"]').val();
      const weight = $(el).find('input[placeholder="Berat"]').val();
      const sku = $(el).find('[data-key]').attr('data-key');

      const attributes = [];
      $(el).find('td[data-attribute-index]').each((i, el) => {
        const attributeName = $(`#variant-attribute-${i}`).val();
        const attributeOption = $(el).text();

        attributes.push({
          [attributeName]: attributeOption
        });
      });

      variations.push({
        price,
        stock,
        weight,
        sku,
        attributes
      });
    });

    const imageUrls = [];
    $('.images img').each((_, el) => {
      const sourceUrl = $(el).attr('src');
      // image from external source/import
      if (!sourceUrl.includes(location.host)) {
        imageUrls.push(sourceUrl);
      }
    });

    const productDetails = {
      name: $('#product-name').val(),
      description: $('#product-description').val(),
      condition: $('[name="product-condition"]:checked').val(),
      variations,
      image_urls: imageUrls,
      deleted_images: deletedImageIds,
    };

    if (!variations.length) {
      productDetails.price = $('#product-price').val();
      productDetails.stock = $('#product-stock').val();
    }

    if (isEditForm) {
      productDetails._method = 'PATCH';
    }


    $.post(isEditForm ? `/api/products/${product.id}` : '/api/products', productDetails)
      .then(response => {

        if (imageFiles.length) {
          const formData = new FormData;
          imageFiles.forEach((image, index) => {
            formData.append(`images[${index}]`, image);
          });

          $.ajax({
            url: `/api/products/${response.data.product_id}/images`,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
          });
        }

        toast({ text: response.message });
      });
  });

  if (isEditForm) {
    getProductDetail();
  }
});