$(function(){

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

  const importer = (jsonFile) => {
    new Importer(jsonFile, (product) => {
      console.log(product);

      $('#product-name').val(product.name);
      $('#product-description').val(product.description);
      $('#product-price').val(product.originalPrice);
      $('#product-stock').val(product.stock);

      // generate variant attributes and options section
      $('#variant-options').empty();
      const variantAttributes = product.variants.map(item => ({ value: item.name, text: item.name }));
      product.variants.forEach((variant, index) => {
        const variantOptionEl = ProductVariantOptionEl({
          index,
          attributes: variantAttributes,
        });

        $('#variant-options').append(variantOptionEl);

        $(`#attribute-options-${index}`).selectize({
          maxItems: null,
          valueField: 'id',
          labelField: 'title',
          searchField: 'title',
          options: variant.options.map(option => ({ id: option.name, title: option.name })),
          create: true
        });
      });

      // generate table
      const variantCombinations = getVariantCombinations(product.variants);
      console.log(variantCombinations);
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

  $('#variant-options').on('click', 'button[name="btn-remove-variant"]', function(e){
    $(this).closest('.row').remove();
  });

});