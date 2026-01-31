$(function(){

  let orderId;

  const BAGDE_CLASS = {
    pending: 'text-bg-warning',
    paid: 'text-bg-success',
    shipped: 'text-bg-info',
    cancelled: 'text-bg-danger'
  }

  // Initialize review forms
  initReviewForms();

  function initReviewForms() {
    initStarRating();
    initImageUpload();
    initReviewSubmit();
  }

  function initStarRating() {
    $(document).on('mouseenter', '.star-rating .star', function() {
      const rating = $(this).data('rating');
      $(this).siblings('.star').each(function() {
        $(this).toggleClass('active', $(this).data('rating') <= rating);
      });
    });

    $(document).on('mouseleave', '.star-rating .star', function() {
      const currentRating = $(this).closest('.star-rating').find('input[name="rating"]').val();
      $(this).siblings('.star').each(function() {
        $(this).toggleClass('active', $(this).data('rating') <= currentRating);
      });
    });

    $(document).on('click', '.star-rating .star', function() {
      const rating = $(this).data('rating');
      const $container = $(this).closest('.star-rating');
      $container.find('input[name="rating"]').val(rating);
      $container.find('.star').each(function() {
        $(this).toggleClass('active', $(this).data('rating') <= rating);
      });
      $container.find('.invalid-feedback').remove();
    });
  }

  function initImageUpload() {
    $(document).on('click', '.image-upload-area', function(e) {
      e.preventDefault();
      e.stopPropagation();
      $(this).find('input[type="file"]').trigger('click');
    });

    $(document).on('click', '.image-upload-area input[type="file"]', function(e) {
      e.stopPropagation();
    });

    $(document).on('change', '.image-upload-area input[type="file"]', function(e) {
      const files = Array.from(e.target.files);
      const $form = $(this).closest('.review-form');
      const $previewGrid = $form.find('.image-preview-grid');
      const maxImages = 5;
      const maxFileSize = 2 * 1024 * 1024; // 2MB
      const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];

      // Get current files from DataTransfer stored in data attribute
      let dataTransfer = $form.data('filesDataTransfer');
      if (!dataTransfer) {
        dataTransfer = new DataTransfer();
        $form.data('filesDataTransfer', dataTransfer);
      }

      files.forEach((file) => {
        if (dataTransfer.items.length >= maxImages) {
          toast({ text: 'Maksimal 5 gambar per review', type: 'error' });
          return false;
        }

        if (!allowedTypes.includes(file.type)) {
          toast({ text: 'Format gambar harus JPEG, PNG, JPG, GIF, atau WebP', type: 'error' });
          return;
        }

        if (file.size > maxFileSize) {
          toast({ text: 'Ukuran gambar maksimal 2MB', type: 'error' });
          return;
        }

        // Add to DataTransfer
        dataTransfer.items.add(file);

        const reader = new FileReader();
        reader.onload = function(e) {
          const previewId = 'preview-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
          const $previewItem = $(`
            <div class="image-preview-item" id="${previewId}" data-file-name="${file.name}">
              <img src="${e.target.result}" alt="Preview">
              <button type="button" class="remove-image" data-preview-id="${previewId}" data-file-name="${file.name}">
                <i class="bi bi-x"></i>
              </button>
            </div>
          `);
          $previewGrid.append($previewItem);
        };
        reader.readAsDataURL(file);
      });

      // Update the file input with new DataTransfer
      const newDataTransfer = new DataTransfer();
      for (let i = 0; i < dataTransfer.files.length; i++) {
        newDataTransfer.items.add(dataTransfer.files[i]);
      }
      $(this)[0].files = newDataTransfer.files;
    });

    $(document).on('click', '.remove-image', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const previewId = $(this).data('preview-id');
      const fileName = $(this).data('file-name');
      const $form = $(this).closest('.review-form');
      const $previewItem = $('#' + previewId);
      const $fileInput = $form.find('.image-upload-area input[type="file"]');

      // Get DataTransfer
      let dataTransfer = $form.data('filesDataTransfer');
      if (!dataTransfer) return;

      // Remove file from DataTransfer by name
      const newDataTransfer = new DataTransfer();
      for (let i = 0; i < dataTransfer.files.length; i++) {
        if (dataTransfer.files[i].name !== fileName) {
          newDataTransfer.items.add(dataTransfer.files[i]);
        }
      }
      dataTransfer = newDataTransfer;
      $form.data('filesDataTransfer', dataTransfer);

      // Update file input
      $fileInput[0].files = dataTransfer.files;

      $previewItem.remove();
    });
  }

  function initReviewSubmit() {
    $(document).on('submit', '.review-form', function(e) {
      e.preventDefault();
      submitReview($(this));
    });
  }

  function submitReview($form) {
    const productId = $form.data('product-id');
    const rating = $form.find('input[name="rating"]').val();
    const title = $form.find('input[name="title"]').val();
    const content = $form.find('textarea[name="content"]').val();
    const orderId = $form.find('input[name="order_id"]').val();

    // Get files from DataTransfer
    const dataTransfer = $form.data('filesDataTransfer');
    const files = dataTransfer ? Array.from(dataTransfer.files) : [];

    // Validation
    if (!rating) {
      $form.find('.star-rating').after('<div class="invalid-feedback d-block">Rating wajib diisi</div>');
      return;
    }

    // Create form data
    const formData = new FormData();
    formData.append('rating', rating);
    formData.append('title', title || '');
    formData.append('content', content || '');
    formData.append('order_id', orderId);

    files.forEach((file, index) => {
      formData.append('images[' + index + ']', file);
    });

    // Show loading state
    const $submitBtn = $form.find('.btn-submit-review');
    $submitBtn.prop('disabled', true).addClass('loading');
    $form.find('.invalid-feedback').remove();

    $.ajax({
      url: '/api/products/' + productId + '/reviews',
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
    }).then(response => {
      // Success
      toast({ text: response.message });

      // Replace form with success message
      const $reviewItem = $form.closest('.review-item');
      $reviewItem.html(`
        <div class="review-success-message">
          <div class="success-icon">✓</div>
          <div class="success-text">Review Berhasil Dikirim</div>
          <div class="success-subtext">Terima kasih atas review Anda!</div>
        </div>
      `);

    }).catch(xhr => {
      $submitBtn.prop('disabled', false).removeClass('loading');
    });
  }


  const table = $('#table-orders').DataTable({
    serverSide: true,
    ordering: false,
    ajax: {
      url: '/api/orders?view=datatable',
      dataSrc: function(items) {
        return items.data.map(item => {
          const orderItems = item.order_details.slice(0, 5).map(item => `
              <a href="/products/${item.product.slug}" target="_blank">
                <img class="tiny" src="${item.product.image.url}" />
              </a>
            `).join('');

          let dropdownMenu = '';
          if (userType === 'admin') {
            dropdownMenu += `<li><button name="btn-show-resi-number" class="dropdown-item" data-id="${item.id}" data-resi-number="${item.resi_number}">Atur Pengiriman</a></li>`;
          }

          if (item.is_cancelable) {
            dropdownMenu += `<li><button name="btn-cancel-confirmation" class="dropdown-item text-danger" data-id="${item.id}">Batalkan Order</button></li>`;
          }

          return [
            item.code,
            item.recipient_name,
            orderItems,
            `<p class="m-0 text-md-end">${currencyFormat.format(item.grand_total)}</p>`,
            `<span class="badge ${BAGDE_CLASS[item.status]}">${item.order_status}</span>`,
            `
              <div class="d-flex justify-content-end">
                <div class="btn-group" role="group">
                  <a href="/orders/${item.code}" class="btn btn-dark btn-sm">Lihat Detail Transaksi</a>
                  ${dropdownMenu
                    ? `
                    <div class="btn-group" role="group">
                      <button type="button" class="btn btn-dark btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"></button>
                      <ul class="dropdown-menu">${dropdownMenu}</ul>
                    </div>`
                    : ''
                  }
                </div>
              </div>
            `
          ]
        });
      }
    },
    columnDefs: [
      {
        width: '5%',
        targets: 0
      },
      {
        width: 140,
        targets: 3
      },
      {
        width: 120,
        targets: 4
      },
      {
        width: '18%',
        targets: 5
      },
    ]
  });

  $(document).on('click', 'button[name="btn-show-resi-number"]', function(e){
    e.preventDefault();
    orderId = $(this).data('id');
    const resiNumber = $(this).data('resi-number');

    $('#resi_number').val(resiNumber);
    $('#resiNumberModal').modal('show');
  });

  $(document).on('click', 'button[name="btn-cancel-confirmation"]', function(e){
    e.preventDefault();
    orderId = $(this).data('id');

    $('#cancelConfirmationModal').modal('show');
  });

  $('button[name="btn-save-resi-number"]').on('click', function(e){
    e.preventDefault();
    const data = {
      _method: 'PATCH',
      resi_number: $('#resi_number').val(),
    }

    $.post(`/api/orders/${orderId}/resi-number`, data).then(response => {
      $('#resiNumberModal').modal('hide');
      toast({ text: response.message });
      table.ajax.reload();
    });
  });

  $('button[name="btn-cancel-order"]').on('click', function(e){
    e.preventDefault();

    const data = {
      _method: 'PATCH',
      resi_number: orderId,
    }

    $.post(`/api/orders/${orderId}/cancel`, data).then(response => {
      $('#cancelConfirmationModal').modal('hide');
      toast({ text: response.message });
      table.ajax.reload();
    });
  });

});