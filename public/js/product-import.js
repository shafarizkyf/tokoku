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

      document.getElementById('preview-container').innerHTML = previewCards;
      document.getElementById('btn-import').classList.remove('d-none');
      document.getElementById('btn-reset').classList.remove('d-none');
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

  document.getElementById('btn-reset').addEventListener('click', function(e){
    if (file) {
      myDropzone.removeFile(file);
      document.querySelector('#dropzone p').classList.remove('d-none');
      file = null;
    }

    document.getElementById('preview-container').innerHTML = "";
    document.getElementById('btn-import').classList.add('d-none');
    this.classList.add('d-none');
  });

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