/**
 * Studiofy Gallery View
 * @package Studiofy
 * @version 2.0.7
 */
jQuery(document).ready(function($) {

    $('#studiofy-proofing-form').on('submit', function(e) {
        e.preventDefault();
        
        var selected = [];
        $(this).find('input[name="selected_photos[]"]:checked').each(function() {
            selected.push(parseInt($(this).val()));
        });

        if (selected.length === 0) {
            alert('Please select at least one photo.');
            return;
        }

        var btn = $(this).find('button');
        btn.prop('disabled', true).text('Saving...');

        wp.apiFetch({
            path: '/studiofy/v1/gallery/proof',
            method: 'POST',
            headers: { 'X-WP-Nonce': studiofyGallery.nonce },
            data: {
                gallery_id: studiofyGallery.current_id,
                photos: selected
            }
        }).then(response => {
            btn.text('Selections Saved');
            alert('Thank you! We have received your ' + response.count + ' selections.');
        }).catch(err => {
            alert('Error saving selection. Please try again.');
            btn.prop('disabled', false).text('Submit Selections');
        });
    });
});
