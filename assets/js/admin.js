/**
 * Studiofy Admin Core
 * @package Studiofy
 * @version 2.0.6
 */
jQuery(document).ready(function($){
    
    // 1. Modal Logic
    function setupModal(triggerId, modalId) {
        $(triggerId).click(function(e){ e.preventDefault(); $(modalId).removeClass('studiofy-hidden'); });
        $(modalId + ' .close-modal').click(function(e){ e.preventDefault(); $(modalId).addClass('studiofy-hidden'); });
    }
    setupModal('#btn-new-customer', '#modal-new-customer');
    setupModal('#btn-new-appt', '#modal-new-appt');

    // 2. WP Color Picker
    $('.studiofy-color-field').wpColorPicker();
    
    // 3. Delete Confirmation
    $('.delete-link').click(function(){ return confirm('Are you sure you want to delete this item?'); });

    // 4. Form Validation (Frontend)
    $('#studiofy-customer-form').on('submit', function(e) {
        let valid = true;
        let errors = [];

        // Phone Validation (XXX-XXX-XXXX or similar)
        const phone = $('input[name="phone"]').val();
        if(phone && !phone.match(/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im)) {
            valid = false;
            errors.push('Invalid phone format.');
        }

        // Email Validation
        const email = $('input[name="email"]').val();
        if(!email.match(/^[^@]+@[^@]+\.[a-zA-Z]{2,}$/)) {
            valid = false;
            errors.push('Invalid email address.');
        }

        // Zip Code
        const zip = $('input[name="addr_zip"]').val();
        if(zip && !zip.match(/^\d{5}(?:[-\s]\d{4})?$/)) {
            valid = false;
            errors.push('Invalid Zip Code.');
        }

        if(!valid) {
            e.preventDefault();
            alert(errors.join('\n'));
        }
    });
});
