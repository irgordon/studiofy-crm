/**
 * Studiofy Gallery View
 * @package Studiofy
 * @version 2.0.4
 */
jQuery(document).ready(function($) {
    const ApiRoot = studiofyGallery.root + 'studiofy/v1/';

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

        $.ajax({
            url: ApiRoot + 'gallery/proof',
            method: 'POST',
            contentType: 'application/json',
            beforeSend: function ( xhr ) {
                xhr.setRequestHeader( 'X-WP-Nonce', studiofyGallery.nonce );
            },
            data: JSON.stringify({
                gallery_id: studiofyGallery.current_id,
                photos: selected
            }),
            success: function(response) {
                btn.text('Selections Saved');
                alert('Thank you! We have received your ' + response.count + ' selections.');
            },
            error: function(err) {
                alert('Error saving selection. Please try again.');
                btn.prop('disabled', false).text('Submit Selections');
            }
        });
    });
});
