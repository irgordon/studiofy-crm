/**
 * Studiofy Frontend Gallery JS
 * @version 2.2.27
 */
jQuery(document).ready(function($) {
    
    // Toggle Approval
    $('.proof-btn.approve').click(function() {
        const item = $(this).closest('.studiofy-grid-item');
        item.removeClass('rejected').toggleClass('approved');
    });

    // Toggle Rejection
    $('.proof-btn.reject').click(function() {
        const item = $(this).closest('.studiofy-grid-item');
        item.removeClass('approved').toggleClass('rejected');
    });

    // Submit
    $('.studiofy-submit-proof').click(function() {
        const btn = $(this);
        const galleryId = btn.data('id');
        const selections = [];

        $('.studiofy-grid-item').each(function() {
            const id = $(this).data('file-id');
            if ($(this).hasClass('approved')) {
                selections.push({ file_id: id, status: 'approved' });
            } else if ($(this).hasClass('rejected')) {
                selections.push({ file_id: id, status: 'rejected' });
            }
        });

        if (selections.length === 0) {
            alert('Please select at least one image (Approve or Reject) before submitting.');
            return;
        }

        if (!confirm('Are you sure you want to submit your selections? This will notify the photographer.')) return;

        btn.text('Sending...').prop('disabled', true);

        $.post(studiofyProofSettings.ajax_url, {
            action: 'studiofy_submit_proof',
            nonce: studiofyProofSettings.nonce,
            gallery_id: galleryId,
            selections: selections
        }, function(res) {
            if (res.success) {
                alert(res.data.message);
                btn.text('Submitted');
            } else {
                alert('Error: ' + res.data);
                btn.text('Try Again').prop('disabled', false);
            }
        });
    });
});
