$(function(){
  let banners = [];
  let banner;

  const fetchBanners = () => {
    $.getJSON('/api/banners').then(response => {
      banners = response;
      const previews = response.map((banner, index) => `
          <div class="d-flex flex-column align-items-start gap-2">
            <img src="${banner.url}" class="banner-preview">
            <button class="btn btn-sm btn-dark" name="btn-edit" data-index="${index}">Edit</button>
          </div>
        `);
      $('#previews').empty().append(previews.join(''));
    });
  }

  $(document).on('click', 'button[name="btn-edit"]', function(e){
    e.preventDefault();
    banner = banners[$(this).data('index')];
    $('.modal-title').text('Edit Banner');

    $('#link').val(banner.link);
    $('#description').val(banner.description);

    $('#bannerInputModal').modal('show');
  });

  $('button[name="btn-create-new"]').on('click', function(e){
    e.preventDefault();
    banner = null;
    $('.modal-title').text('Buat Banner');
    $('input, textarea').val('');

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

    if (banner) {
      formData.append('_method', 'PATCH');
    }

    $.ajax({
      url: banner ? `/api/banners/${banner.id}` : '/api/banners',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
    }).then(response => {
      $('input, textarea').val('');
      $('#bannerInputModal').modal('hide');
      toast({ text: response.message });
      fetchBanners();
    });
  });

  fetchBanners();
});