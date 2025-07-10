class ImportCardElement {
  static renderCards(data) {
    const previewCards = data
      .map((product, i) => ImportProductCardEl({
        imageUrl: product.imageUrl,
        price: product.discountPrice || product.normalPrice,
        title: product.name,
        index: i
      }))
      .join('');

    document.getElementById('preview-container').innerHTML = previewCards;
  }

  static updateSelectionCounter() {
    const selectedCount = this.checkedCount();
    // update selected counter
    document.getElementById('item-selected-count').innerText = selectedCount;
    // toggle radio select all
    document.getElementById('radio-select-all').checked = selectedCount === this.count();
    // toggle radio deselect all
    document.getElementById('radio-deselect-all').checked = selectedCount === 0;
    // toggle visibility of view unselected
    document.getElementById('cb-view-unselected').parentElement.classList.toggle('d-none', selectedCount === this.count());

    if (selectedCount !== this.count()) {
      // toggle counter bg color
      document.getElementById('import-information').classList.remove('alert-dark');
      document.getElementById('import-information').classList.add('alert-warning');
    } else {
      // toggle counter bg color
      document.getElementById('import-information').classList.add('alert-dark');
      document.getElementById('import-information').classList.remove('alert-warning');

      // make sure all cards visible when `view unselected` still on but no longer has unselected card
      document.querySelectorAll('#preview-container input[type="checkbox"]:checked').forEach(el => {
        el.closest('.card').classList.remove('d-none');
      });

      // same reason as above, the case is, when 99/100 cards were visible, then user click selected so -> 100/100
      document.getElementById('cb-view-unselected').checked = false;
    }
  }

  static count() {
    return document.querySelectorAll('#preview-container .card').length;
  }

  static checkedCount() {
    return document.querySelectorAll('#preview-container input[type="checkbox"]:checked').length;
  }

  static uncheckedCount() {
    return document.querySelectorAll('#preview-container input[type="checkbox"]:not(:checked)').length;
  }

  static resetInput() {
    // clear previews
    document.getElementById('preview-container').innerHTML = "";

    // hide input/buttons
    document.getElementById('btn-import').classList.add('d-none');
    document.getElementById('btn-reset').classList.add('d-none');
    document.getElementById('import-information').classList.add('d-none');
    document.getElementById('search').parentElement.classList.add('d-none');

    // reset input to default state
    document.getElementById('cb-view-unselected').checked = false;
    document.getElementById('radio-select-all').checked = true;
    document.getElementById('search').value = "";

    document.getElementById('dropzone').classList.remove('d-none');
  }
}

(function(){
  let file = null;
  let fileContent = null;

  const readFile = (file) => {
    const reader = new FileReader();
    reader.onload = function(e) {
      const text = e.target.result;
      fileContent = JSON.parse(text);

      ImportCardElement.renderCards(fileContent.data);

      document.getElementById('item-selected-count').innerHTML = fileContent.data.length;
      document.getElementById('item-total-count').innerHTML = fileContent.data.length;
      document.getElementById('import-information').classList.remove('d-none');

      document.getElementById('search').parentElement.classList.remove('d-none');
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

  on(document, 'click', '.card input[type="checkbox"]', function(e){
    ImportCardElement.updateSelectionCounter();
    e.target.nextSibling.nextSibling.innerText = e.target.checked ? 'Selected' : 'Skipped';
  });

  document.getElementsByName('radio-selection').forEach(radioSelectionEl => {
    radioSelectionEl.addEventListener('click', function(e){
      const shouldSelectAll = e.target.value === '1';
      document.querySelectorAll('#preview-container input[type="checkbox"]').forEach((el, i) => {
        el.checked = shouldSelectAll;
        el.parentElement.querySelector('label').innerText = shouldSelectAll ? 'Selected' : 'Skipped';
      });

      ImportCardElement.updateSelectionCounter();
    });
  });

  // toggle view selected/unselected
  document.getElementById('cb-view-unselected').addEventListener('click', function(){
    const isActive = this.checked;
    document.querySelectorAll('#preview-container input[type="checkbox"]:checked').forEach(el => {
      if (isActive) {
        el.closest('.card').classList.add('d-none');
      } else {
        el.closest('.card').classList.remove('d-none');
      }
    });
  });

  // search
  document.getElementById('search').addEventListener('keyup', function(e){
    const keyword = e.target.value;
    const isViewUnselected = document.getElementById('cb-view-unselected').checked;
    document.querySelectorAll('#preview-container .card h5').forEach(el => {
      const textMatch = el.textContent.toLowerCase().includes(keyword);
      const isSelected = el.parentElement.querySelector('input[type="checkbox"]').checked;
      const shouldShow = isViewUnselected ? !isSelected && textMatch : textMatch;

      if (shouldShow) {
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

    ImportCardElement.resetInput();
  });

  // import
  document.getElementById('btn-import').addEventListener('click', function(e){
    e.preventDefault();

    let fileToUpload = file;

    // when has unchecked product, this will create new json file
    if (ImportCardElement.uncheckedCount()) {
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

      fileToUpload = new File([JSON.stringify(updatedFileContent)], "products.json", {
        type: 'application/json'
      });
    }

    const formData = new FormData;
    formData.append('file', fileToUpload);

    $.ajax({
      url: '/api/products/import',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
    }).then(response => {
      ImportCardElement.resetInput();
      alert(response.message);
    });
  });
})();