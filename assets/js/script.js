// Custom JavaScript for Workflow Portal

$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Confirm delete actions
    $('.confirm-delete').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });
    
    // Image preview for file uploads
    $('input[type="file"]').on('change', function() {
        const files = this.files;
        const preview = $(this).data('preview');
        
        if (preview && files.length > 0) {
            const previewContainer = $('#' + preview);
            previewContainer.empty();
            
            Array.from(files).forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewContainer.append(
                            `<div class="col-md-3 mb-3">
                                <img src="${e.target.result}" class="img-thumbnail" alt="Preview">
                            </div>`
                        );
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    });
    
    // DataTable initialization (if library is included)
    if ($.fn.DataTable) {
        $('.data-table').DataTable({
            "pageLength": 25,
            "ordering": true,
            "searching": true,
            "responsive": true
        });
    }
    
    // Notification click handling is done server-side in notifications.php
    
    // Form validation
    $('form').on('submit', function(e) {
        let isValid = true;
        
        $(this).find('[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
    
    // Remove invalid class on input
    $('input, textarea, select').on('input change', function() {
        $(this).removeClass('is-invalid');
    });
});

// Image modal viewer
function viewImage(src) {
    const modal = `
        <div class="modal fade" id="imageModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Image Preview</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="${src}" class="img-fluid" alt="Design Image">
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('body').append(modal);
    $('#imageModal').modal('show');
    $('#imageModal').on('hidden.bs.modal', function() {
        $(this).remove();
    });
}
