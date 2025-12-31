$(document).ready(function () {
    const form = $('#shop-settings-form');
    const btnSave = $('#btn-save');
    const imageInput = $('#image');
    const imagePreview = $('#image-preview');
    const previewContainer = $('#preview-container');
    const currentImage = $('#current-image');
    const currentImageContainer = $('#current-image-container');

    // Load current shop settings
    loadShopSettings();

    // Image preview on file select
    imageInput.on('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                imagePreview.attr('src', e.target.result);
                previewContainer.show();
            };
            reader.readAsDataURL(file);
        } else {
            previewContainer.hide();
        }
    });

    // Form submission
    form.on('submit', function (e) {
        e.preventDefault();

        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        const formData = new FormData(this);

        // Show loading state
        btnSave.prop('disabled', true);
        btnSave.find('.spinner-border').removeClass('d-none');

        $.ajax({
            url: '/api/shop/settings',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                // Show success message
                toast({
                    text: 'Pengaturan toko berhasil disimpan',
                });

                // Reload settings to show updated data
                loadShopSettings();

                // Clear preview
                previewContainer.hide();
                imageInput.val('');
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    // Validation errors
                    const errors = xhr.responseJSON.errors;
                    for (const field in errors) {
                        const input = $(`#${field}`);
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').text(errors[field][0]);
                    }
                } else {
                    toast({
                        text: xhr.responseJSON?.message || 'Terjadi kesalahan saat menyimpan pengaturan'
                    });
                }
            },
            complete: function () {
                // Hide loading state
                btnSave.prop('disabled', false);
                btnSave.find('.spinner-border').addClass('d-none');
            }
        });
    });

    function loadShopSettings() {
        $.ajax({
            url: '/api/shop/settings',
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('token')
            },
            success: function (response) {
                const shop = response.data;

                // Populate form fields
                $('#name').val(shop.name);
                $('#description').val(shop.description);

                // Show current image if exists
                if (shop.image_path) {
                    currentImage.attr('src', `/storage/${shop.image_path}`);
                    currentImageContainer.show();
                } else {
                    currentImageContainer.hide();
                }
            },
            error: function (xhr) {
                if (xhr.status === 404) {
                    // No shop settings yet, that's okay
                    console.log('No shop settings found, ready to create new');
                } else {
                    console.error('Error loading shop settings:', xhr);
                }
            }
        });
    }
});
