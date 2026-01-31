$(function () {
  let currentReviewId = null;
  let currentReviewData = null;

  const table = $('#table-reviews').DataTable({
    serverSide: true,
    ajax: {
      url: '/api/admin/reviews?view=datatable',
      dataSrc: function (items) {
        return items.data.map(item => {
          // Generate stars
          let stars = '';
          for (let i = 1; i <= 5; i++) {
            stars += i <= item.rating ? '★' : '☆';
          }

          // Generate status badge
          let statusBadge = '';
          if (item.status === 'approved') {
            statusBadge = '<span class="review-status-badge review-status-approved">✓ Approved</span>';
          } else if (item.status === 'pending') {
            statusBadge = '<span class="review-status-badge review-status-pending">⏳ Pending</span>';
          } else if (item.status === 'rejected') {
            statusBadge = '<span class="review-status-badge review-status-rejected">✕ Rejected</span>';
          }

          // Generate images thumbnails
          let imagesHtml = '';
          if (item.images && item.images.length > 0) {
            imagesHtml = '<div class="review-images-thumbnails">';
            item.images.slice(0, 2).forEach(img => {
              imagesHtml += `<img src="/storage/${img.image_path}" alt="Review image">`;
            });
            if (item.images.length > 2) {
              imagesHtml += `<span>+${item.images.length - 2}</span>`;
            }
            imagesHtml += '</div>';
          } else {
            imagesHtml = '<span class="text-muted">-</span>';
          }

          // Content preview
          let contentHtml = '';
          if (item.title) {
            contentHtml += `<div class="review-title">${item.title}</div>`;
          }
          if (item.content) {
            const preview = item.content.length > 50 ? item.content.substring(0, 50) + '...' : item.content;
            contentHtml += `<div class="review-content-preview">${preview}</div>`;
          }

          return [
            `
              <div class="review-product-cell">
                <img src="${item.images.length ? `/storage/${item.images[0].image_path}` : ''}" alt="">
                <a href="/products/${item.product.slug}" target="_blank" class="product-name">${item.product.name}</a>
              </div>
            `,
            `
              <div class="review-user-cell">
                <div class="review-user-avatar">${item.user.name.charAt(0).toUpperCase()}</div>
                <div class="review-user-info">
                  <div class="review-user-name">${item.user.name}</div>
                  <div class="review-user-email">${item.user.email}</div>
                </div>
              </div>
            `,
            `<div class="review-rating">${stars}</div>`,
            contentHtml,
            imagesHtml,
            `<div class="review-date">${new Date(item.created_at).toLocaleDateString('id-ID')}</div>`,
            `
              <div class="review-actions">
                <button class="btn btn-outline-dark btn-sm btn-view-review" data-id="${item.id}">Lihat</button>
                ${item.status === 'pending' ? `
                  <button class="btn btn-success btn-sm btn-approve-review" data-id="${item.id}">✓</button>
                  <button class="btn btn-danger btn-sm btn-reject-review" data-id="${item.id}">✕</button>
                ` : ''}
                <button class="btn btn-outline-danger btn-sm btn-delete-review" data-id="${item.id}">🗑</button>
              </div>
            `
          ];
        });
      },
      data: function (d) {
        d.rating = $('#filter-rating').val();
        d.status = $('#filter-status').val();
        d.search = d.search?.value;
      }
    },
    columnDefs: [
      {
        width: '25%',
        targets: 0
      },
      {
        width: '15%',
        targets: 1
      },
      {
        width: '100px',
        targets: 6
      }
    ],
    pagingType: 'simple_numbers',
    language: {
      search: 'Cari:',
      lengthMenu: 'Tampilkan _MENU_ data',
      zeroRecords: 'Tidak ada data',
      info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
      infoEmpty: 'Tidak ada data',
      paginate: {
        first: 'Awal',
        last: 'Akhir',
        next: '›',
        previous: '‹'
      }
    }
  });

  // Filter handlers
  $('#filter-rating, #filter-status').on('change', function () {
    table.ajax.reload();
  });

  // View review detail
  $(document).on('click', '.btn-view-review', function () {
    const reviewId = $(this).data('id');
    currentReviewId = reviewId;
    
    // Find review data from table
    const rowData = table.rows().data().toArray().find(row => {
      return row.some(cell => cell.includes(`data-id="${reviewId}"`));
    });
    
    // Fetch full review data
    $.getJSON(`/api/reviews/${reviewId}`)
      .then(response => {
        if (response.success) {
          currentReviewData = response.data;
          showReviewModal(response.data);
        }
      });
  });

  function showReviewModal(review) {
    // Generate stars
    let stars = '';
    for (let i = 1; i <= 5; i++) {
      stars += i <= review.rating ? '★' : '☆';
    }

    // Generate status badge
    let statusBadge = '';
    if (review.status === 'approved') {
      statusBadge = '<span class="review-status-badge review-status-approved">✓ Approved</span>';
    } else if (review.status === 'pending') {
      statusBadge = '<span class="review-status-badge review-status-pending">⏳ Pending</span>';
    } else if (review.status === 'rejected') {
      statusBadge = '<span class="review-status-badge review-status-rejected">✕ Rejected</span>';
    }

    // Generate images
    let imagesHtml = '';
    if (review.images && review.images.length > 0) {
      imagesHtml = '<h6>Foto Ulasan</h6><div class="review-detail-images-list">';
      review.images.forEach(img => {
        imagesHtml += `<a href="${img.path}" target="_blank"><img src="${img.path}" alt="Review image"></a>`;
      });
      imagesHtml += '</div>';
    }

    console.log('review', review);

    const html = `
      <div class="review-detail-header">
        <div class="review-detail-product">
          <img src="${review.images.length ? review.images[0].path : ''}">
          <h6>${review.product?.name || ''}</h6>
          ${statusBadge}
        </div>
        <div class="review-detail-user">
          <div class="review-user-name">${review.user.name}</div>
          <div class="review-detail-rating">${stars}</div>
        </div>
      </div>
      ${review.title ? `<div class="review-detail-title">${review.title}</div>` : ''}
      ${review.content ? `<div class="review-detail-content">${review.content}</div>` : ''}
      ${imagesHtml}
      <div class="review-detail-meta">
        <span>Diposting: ${new Date(review.created_at).toLocaleString('id-ID')}</span>
        ${review.is_verified_purchase ? '<span class="review-detail-status">✓ Pembelian Terverifikasi</span>' : ''}
      </div>
    `;

    $('#review-detail-content').html(html);
    $('#reviewDetailModal').modal('show');

    // Show/hide action buttons based on status
    if (review.status === 'pending') {
      $('#btn-approve-review').show();
      $('#btn-reject-review').show();
    } else {
      $('#btn-approve-review').hide();
      $('#btn-reject-review').hide();
    }
  }

  // Approve review
  $(document).on('click', '#btn-approve-review, .btn-approve-review', function (e) {
    if (e.target.id === 'btn-approve-review') {
      e.preventDefault();
    }
    const reviewId = $(this).data('id') || currentReviewId;
    
    $.ajax({
      url: `/api/admin/reviews/${reviewId}/status`,
      method: 'PATCH',
      data: {
        status: 'approved'
      },
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    }).then(response => {
      toast({ text: response.message });
      $('#reviewDetailModal').modal('hide');
      table.ajax.reload();
    }).catch(xhr => {
      toast({ text: xhr.responseJSON?.message || 'Gagal menyetujui ulasan', type: 'error' });
    });
  });

  // Reject review
  $(document).on('click', '#btn-reject-review, .btn-reject-review', function (e) {
    if (e.target.id === 'btn-reject-review') {
      e.preventDefault();
    }
    const reviewId = $(this).data('id') || currentReviewId;
    
    $.ajax({
      url: `/api/admin/reviews/${reviewId}/status`,
      method: 'PATCH',
      data: {
        status: 'rejected'
      },
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    }).then(response => {
      toast({ text: response.message });
      $('#reviewDetailModal').modal('hide');
      table.ajax.reload();
    }).catch(xhr => {
      toast({ text: xhr.responseJSON?.message || 'Gagal menolak ulasan', type: 'error' });
    });
  });

  // Delete review
  $(document).on('click', '.btn-delete-review', function () {
    currentReviewId = $(this).data('id');
    $('#deleteReviewModal').modal('show');
  });

  $('button[name="btn-confirm-modal"]').on('click', function () {
    $.ajax({
      url: `/api/admin/reviews/${currentReviewId}`,
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    }).then(response => {
      toast({ text: response.message });
      $('#deleteReviewModal').modal('hide');
      table.ajax.reload();
    }).catch(xhr => {
      toast({ text: xhr.responseJSON?.message || 'Gagal menghapus ulasan', type: 'error' });
    });
  });
});
