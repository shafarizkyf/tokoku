(function(){

  const myDropzone = new Dropzone("#dropzone", {
    url: '/api/products/import',
    paramName: 'file',
    maxFilesize: 0.5,
  });

  console.log(myDropzone);
})();