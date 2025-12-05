/**
 * Studiofy Admin Core
 * @package Studiofy
 * @version 2.0.5
 */
jQuery(document).ready(function($){
    // Modals
    function setupModal(triggerId, modalId) {
        $(triggerId).click(function(e){ e.preventDefault(); $(modalId).removeClass('studiofy-hidden'); });
        $(modalId + ' .close-modal').click(function(e){ e.preventDefault(); $(modalId).addClass('studiofy-hidden'); });
    }
    setupModal('#btn-new-customer', '#modal-new-customer');
    setupModal('#btn-new-appt', '#modal-new-appt');

    // WP Color Picker
    $('.studiofy-color-field').wpColorPicker();
    
    // Delete Confirmation
    $('.delete-link').click(function(){ return confirm('Are you sure you want to delete this item?'); });
});
