/**
 * Studiofy Frontend Proofing
 * @package Studiofy
 * @version 2.1.11
 */
jQuery(document).ready(function($) {
    $('#proofing-form').on('submit', function(e) {
        e.preventDefault();
        const data = $(this).serialize();
        
        $.post(studiofyProof.ajax_url, {
            action: 'studiofy_submit_proof',
            gallery_id: studiofyProof.gallery_id,
            data: data
        }, function(response) {
            if(response.success) {
                alert(response.data.message);
            }
        });
    });
});
