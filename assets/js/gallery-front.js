/**
 * Studiofy Gallery Frontend
 * @version 2.3.10
 */
jQuery(document).ready(function($) {
    
    // Status Toggles
    $('.proof-btn').click(function(e) {
        e.preventDefault();
        e.stopPropagation(); // Prevent bubbling if needed
        
        const $item = $(this).closest('.studiofy-grid-item');
        const action = $(this).hasClass('approve') ? 'approve' : 'reject';
        
        // Toggle Logic
        if (action === 'approve') {
            if ($item.hasClass('approved')) {
                $item.removeClass('approved'); // Toggle off
            } else {
                $item.removeClass('rejected').addClass('approved');
            }
        } else {
            if ($item.hasClass('rejected')) {
                $item.removeClass('rejected'); // Toggle off
            } else {
                $item.removeClass('approved').addClass('rejected');
            }
        }
    });

    // Submit Logic
    $('.studiofy-submit-proof').click(function() {
        const btn = $(this);
        const galleryId = btn.data('id');
        
        let selections = [];
        let hasSelections = false;

        $('.studiofy-grid-item').each(function() {
            const id = $(this).data('file-id');
            if ($(this).hasClass('approved')) {
                selections.push({ file_id: id, status: 'approved' });
                hasSelections = true;
            } else if ($(this).hasClass('rejected')) {
                selections.push({ file_id: id, status: 'rejected' });
                hasSelections = true;
            }
        });

        if (!hasSelections) {
            if (!confirm('You have not selected any images. Submit anyway?')) return;
        } else {
            if (!confirm(`You are submitting ${selections.filter(s=>s.status==='approved').length} approved images. Continue?`)) return;
        }

        btn.text('Submitting...').prop('disabled', true);

        $.post(studiofyProofSettings.ajax_url, {
            action: 'studiofy_submit_proof',
            gallery_id: galleryId,
            selections: selections,
            nonce: studiofyProofSettings.nonce
        }, function(res) {
            if (res.success) {
                alert('Thank you! Your selections have been sent to the photographer.');
                // UPDATED: Redirect to Homepage
                if (res.data.redirect_url) {
                    window.location.href = res.data.redirect_url;
                }
            } else {
                alert('Error: ' + res.data);
                btn.text('Submit Selections').prop('disabled', false);
            }
        }).fail(function() {
            alert('Server error. Please try again.');
            btn.text('Submit Selections').prop('disabled', false);
        });
    });
});
