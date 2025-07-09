(function(){
  let file = null;
  let fileContent = null;
  const readFile = (file) => {
    const reader = new FileReader();
    reader.onload = function(e) {
      const text = e.target.result;
      fileContent = JSON.parse(text);

      const previewCards = fileContent.data
        .map((product, i) => ImportProductCardEl({
          imageUrl: product.imageUrl,
          price: product.discountPrice || product.normalPrice,
          title: product.name,
          index: i
        }))
        .join('');

      document.getElementById('item-selected-count').innerHTML = fileContent.data.length;
      document.getElementById('item-total-count').innerHTML = fileContent.data.length;
      document.getElementById('import-information').classList.remove('d-none');

      document.getElementById('preview-container').innerHTML = previewCards;
      document.getElementById('btn-import').classList.remove('d-none');
      document.getElementById('btn-reset').classList.remove('d-none');
      document.getElementById('dropzone').classList.add('d-none');
    };
    reader.readAsText(file);
  }

  const myDropzone = new Dropzone("#dropzone", {
    url: '/api',
    paramName: 'file',
    maxFilesize: 0.5,
    maxFiles: 1,
    acceptedFiles: 'application/json',
    autoProcessQueue: false,
  });

  myDropzone.on('success', (file, response) => {
    console.log({ file, response });
  });

  myDropzone.on('addedfile', (_file) => {
    file = _file
    readFile(_file);
    document.querySelector('#dropzone p').classList.add('d-none');
  });

  on(document, 'click', 'input[type="checkbox"]', function(e){
    const selectedCount = document.querySelectorAll('#preview-container input[type="checkbox"]:checked').length;
    document.getElementById('item-selected-count').innerText = selectedCount;

    if (selectedCount !== fileContent.data.length) {
      document.getElementById('import-information').classList.remove('alert-dark');
      document.getElementById('import-information').classList.add('alert-warning');
      document.getElementById('btn-view-unselected').classList.remove('d-none');
    } else {
      document.getElementById('import-information').classList.add('alert-dark');
      document.getElementById('import-information').classList.remove('alert-warning');
      document.getElementById('btn-view-unselected').classList.add('d-none');

      // make sure all cards visible when `view unselected` still on but no longer has unselected card
      document.querySelectorAll('#preview-container input[type="checkbox"]:checked').forEach(el => {
        el.closest('.card').classList.remove('d-none');
      });

      // same reason as above, the case is, when 99/100 cards were visible, then user click selected so -> 100/100
      document.getElementById('btn-view-unselected').classList.remove('btn-secondary');
      document.getElementById('btn-view-unselected').classList.add('btn-outline-secondary');
    }

    e.target.nextSibling.nextSibling.innerText = e.target.checked ? 'Selected' : 'Skipped';
  });

  // toggle view selected/unselected
  document.getElementById('btn-view-unselected').addEventListener('click', function(){
    const isActive = this.classList.contains('btn-secondary')

    if (isActive) {
      this.classList.remove('btn-secondary');
      this.classList.add('btn-outline-secondary');
    } else {
      this.classList.remove('btn-outline-secondary');
      this.classList.add('btn-secondary');
    }

    document.querySelectorAll('#preview-container input[type="checkbox"]:checked').forEach(el => {
      if (isActive) {
        el.closest('.card').classList.remove('d-none');
      } else {
        el.closest('.card').classList.add('d-none');
      }
    });
  });

  // reset
  document.getElementById('btn-reset').addEventListener('click', function(e){
    const unselectedCount = document.querySelectorAll('#preview-container input[type="checkbox"]:not(:checked)').length;

    if (unselectedCount) {
      const isConfirmed = confirm('Are you sure?');

      if (!isConfirmed) {
        return;
      }
    }

    if (file) {
      myDropzone.removeFile(file);
      document.querySelector('#dropzone p').classList.remove('d-none');
      file = null;
    }

    document.getElementById('preview-container').innerHTML = "";
    document.getElementById('btn-import').classList.add('d-none');
    document.getElementById('import-information').classList.add('d-none');

    // reset btn-view-unselected state
    document.getElementById('btn-view-unselected').classList.add('d-none');
    document.getElementById('btn-view-unselected').classList.remove('btn-secondary');
    document.getElementById('btn-view-unselected').classList.add('btn-outline-secondary');

    document.getElementById('dropzone').classList.remove('d-none');
    this.classList.add('d-none');
  });

  // import
  document.getElementById('btn-import').addEventListener('click', function(e){
    e.preventDefault();
    const selectedProducts = [];
    const unselectedIndex = []
    document.querySelectorAll('#preview-container input[type="checkbox"]').forEach((el, i) => {
      if (!el.checked) {
        unselectedIndex.push(i);
      }
    });

    fileContent.data.forEach((product, index) => {
      if (unselectedIndex.indexOf(index) === -1) {
        selectedProducts.push(product);
      }
    });

    const updatedFileContent = { ...fileContent }
    updatedFileContent.data = selectedProducts;

    const updatedFile = new File([updatedFileContent], "products.json", {
      type: 'application/json'
    });

    console.log(updatedFileContent, updatedFile);
  });
})();