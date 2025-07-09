(function(){
  let file = null;
  const readFile = (file) => {
    const reader = new FileReader();
    reader.onload = function(e) {
      const text = e.target.result;
      const { data: products } = JSON.parse(text);

      const previewCards = products
        .map(product => ProductCardEl({
          imageUrl: product.imageUrl,
          price: product.discountPrice || product.normalPrice,
          title: product.name
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
})();