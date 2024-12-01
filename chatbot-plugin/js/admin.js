jQuery(document).ready(function($) {
    // Delete resource
    $('.delete-resource').on('click', function() {
        if (confirm('Are you sure you want to delete this resource?')) {
            const button = $(this);
            const id = button.data('id');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_resource',
                    nonce: chatbotAdmin.nonce,
                    id: id
                },
                beforeSend: function() {
                    button.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        button.closest('tr').fadeOut();
                    } else {
                        alert('Failed to delete resource');
                    }
                },
                error: function() {
                    alert('Failed to delete resource');
                },
                complete: function() {
                    button.prop('disabled', false);
                }
            });
        }
    });

    // File upload preview
    $('#resource_file').on('change', function() {
        const file = this.files[0];
        if (file) {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('.upload-preview').remove();
                    $('<img>', {
                        src: e.target.result,
                        class: 'upload-preview',
                        style: 'max-width: 200px; margin-top: 10px;'
                    }).insertAfter('#resource_file');
                };
                reader.readAsDataURL(file);
            }
        }
    });
});
