$(function(){
  let banners = [];
  let banner;

  const fetchBanners = () => {
    $.getJSON('/api/banners').then(response => {
      banners = response;
      const previews = response.map((banner, index) => `
          <div class="d-flex flex-column align-items-start gap-2">
            <img src="${banner.url}" class="banner-preview">
            <div class="btn-group" role="group" aria-label="Button group with nested dropdown">
              <button class="btn btn-sm btn-dark" name="btn-edit" data-index="${index}">Edit</button>
              <div class="btn-group" role="group">
                <button type="button" class="btn btn-dark btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"></button>
                <ul class="dropdown-menu">
                  <li><button class="dropdown-item text-danger" name="btn-delete-confirmation" data-index="${index}">Hapus</button></li>
                </ul>
              </div>
            </div>
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

  $(document).on('click', 'button[name="btn-delete-confirmation"]', function(e){
    e.preventDefault();
    banner = banners[$(this).data('index')];
    $('#confirmModal').modal('show');
  });

  $('button[name="btn-create-new"]').on('click', function(e){
    e.preventDefault();
    banner = null;
    $('.modal-title').text('Buat Banner');
    $('input, textarea').val('');

    $('#bannerInputModal').modal('show');
  });

  $('button[name="btn-confirm-modal"]').on('click', function(e){
    e.preventDefault();
    $.post(`/api/banners/${banner.id}`, {
      _method: 'DELETE'
    }).then(response => {
      toast({ text: response.message });
      fetchBanners();
    });
    $('#confirmModal').modal('hide');
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