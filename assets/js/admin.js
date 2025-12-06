/**
 * Studiofy Admin Core
 * @package Studiofy
 * @version 2.2.3
 */
jQuery(document).ready(function($){
    
    // ... (Modal logic, color picker from v2.1.0) ...
    function setupModal(triggerId, modalId) {
        $(triggerId).click(function(e){ e.preventDefault(); $(modalId).removeClass('studiofy-hidden'); });
        $(modalId + ' .close-modal').click(function(e){ e.preventDefault(); $(modalId).addClass('studiofy-hidden'); });
        $(modalId).click(function(e){ if(e.target === this) $(this).addClass('studiofy-hidden'); });
    }
    setupModal('#btn-new-customer', '#modal-new-customer');
    setupModal('#btn-new-appt', '#modal-new-appt');
    $('.studiofy-color-field').wpColorPicker();
    $('.delete-link').click(function(){ return confirm('Are you sure?'); });

    // Currency Formatter
    $('#project_budget').on('blur', function() {
        let val = $(this).val().replace(/[^\d.]/g, ''); // strip non-numeric
        if (val) {
            val = parseFloat(val).toFixed(2);
            $(this).val('$' + val);
        }
    });

    // ... (Customer Form Validation) ...
});
