$(function(){

  let productVariants = [];

  const generateTableVariantRows = (variantCombinations) => {
    return variantCombinations.map(item => TableRowVariantEditForm({
      attributeLength: variantCombinations.length ? variantCombinations[0].split(' + ').length : 0,
      combination: item.split(' + ')
    })).join('');
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

  const toggleParentPriceAndStock = (isVisible) => {
    $('#product-price').closest('.row').toggleClass('d-none', isVisible);
    $('#product-stock').closest('.row').toggleClass('d-none', isVisible);
  }

  const onSelectItemRemoved = (value, el) => {
    productVariants.forEach((attribute, attributeIndex) => {
      const optionIndex = attribute.options.findIndex(option => option.name === value);
      if (optionIndex !== -1) {
        // remove combination rows
        $(`tr > td:nth-child(${attributeIndex + 2})`).each((i, el) => {
          if ($(el).text() === value) {
            $(el).closest('tr').remove();
          }
        });
      }
    });
  }

  const onSelectItemAdded = (value, el) => {
    const attributeIndex = $(el).closest('.row').data('index');
    if (attributeIndex >= 0) {
      const _productVariants = [...productVariants];

      _productVariants.forEach((item, index) => {
        if (index === attributeIndex) {
          item.options = [{name: value}]
        }
      });

      const newlyAddedCombinations = getVariantCombinations(_productVariants);

      const tableRowsEl = generateTableVariantRows(newlyAddedCombinations);
      $('#table-variants tbody').append(tableRowsEl);
    }
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
      toggleParentPriceAndStock(Boolean(product.variants.length));

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

      // generate table head
      const tableAttributeHeadEl = variantAttributes.map(item => `<th scope="col">${item.text}</th>`).join('');
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

      generateTableRowsCombination();
    });
  };

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

    const index = $('#variant-options .row').length;
    $('#variant-options').append(ProductVariantOptionEl({
      index,
      variant: '',
    }));

    const newVariantRowEl = $(`#variant-options > .row:nth-child(${index + 1})`);
    makeSelectize(newVariantRowEl.find('select'));

    // show table variants
    $('#table-variants').parent().removeClass('d-none');
  });

  $('#variant-options').on('click', 'button[name="btn-remove-variant"]', function(e){
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

    const noLongerHasVariant = $('th').length === 4;
    $('#table-variants').parent().toggleClass('d-none', noLongerHasVariant);
    if (noLongerHasVariant) {
      $('#table-variants tbody').empty();
    } else {
      generateTableRowsCombination();
    }
  });

});