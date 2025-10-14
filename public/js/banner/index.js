$(function(){
  const fetchBanners = () => {
    $.getJSON('/api/banners').then(response => {
      const previews = response.map(banner => `
          <div class="d-flex flex-column align-items-start gap-2">
            <img src="${banner.url}" class="banner-preview">
            <button class="btn btn-sm btn-dark">Edit</button>
          </div>
        `);
      $('#previews').empty().append(previews.join(''));
    });
  }

  $('button[name="btn-create-new"]').on('click', function(e){
    e.preventDefault();
    $('#bannerInputModal').modal('show');
  });

  $('button[name="btn-save-banner"]').on('click', function(e){
    e.preventDefault();
    const formData = new FormData;
    const images = $('#image')[0].files;

    if (images.length) {
      formData.append('image', images[0]);
    }

    formData.append('link', $('#link').val() || '');
    formData.append('description', $('#description').val() || '');

    $.ajax({
      url: '/api/banners',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
    }).then(response => {
      $('input, textarea').val('');
      $('#bannerInputModal').modal('hide');
      toast(response.message);
      fetchBanners();
    });
  });

  fetchBanners();
});