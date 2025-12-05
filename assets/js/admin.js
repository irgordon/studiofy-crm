/**
 * Studiofy Admin Core
 * @package Studiofy
 * @version 2.0.8
 */
jQuery(document).ready(function($){
    
    // Modal Logic
    function setupModal(triggerId, modalId) {
        $(triggerId).click(function(e){ e.preventDefault(); $(modalId).removeClass('studiofy-hidden'); });
        $(modalId + ' .close-modal').click(function(e){ e.preventDefault(); $(modalId).addClass('studiofy-hidden'); });
    }
    setupModal('#btn-new-customer', '#modal-new-customer');
    setupModal('#btn-new-appt', '#modal-new-appt');

    // WP Color Picker
    $('.studiofy-color-field').wpColorPicker();
    
    // Media Uploader
    $('.studiofy-upload-btn').click(function(e) {
        e.preventDefault();
        var button = $(this);
        var targetId = button.data('target');
        var custom_uploader = wp.media({
            title: 'Select Image',
            button: { text: 'Use this image' },
            multiple: false
        }).on('select', function() {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            $(targetId).val(attachment.url);
        }).open();
    });

    // Delete Confirmation
    $('.delete-link').click(function(){ return confirm('Are you sure you want to delete this item?'); });

    // Customer Form Validation
    $('#studiofy-customer-form').on('submit', function(e) {
        let valid = true;
        let errors = [];

        // Check if fields exist and have value before matching to prevent JS errors
        const phoneInput = $('input[name="phone"]');
        const emailInput = $('input[name="email"]');
        const zipInput = $('input[name="addr_zip"]');

        // Phone Validation (Loose check if populated)
        if(phoneInput.length && phoneInput.val() !== '') {
            const phone = phoneInput.val();
            // Allow basic formats: +1-234-567-8900, 1234567890, (123) 456-7890
            if(!phone.match(/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im)) {
                valid = false;
                errors.push('Invalid phone format.');
            }
        }

        // Email Validation (Strict if populated)
        if(emailInput.length && emailInput.val() !== '') {
            const email = emailInput.val();
            if(!email.match(/^[^@]+@[^@]+\.[a-zA-Z]{2,}$/)) {
                valid = false;
                errors.push('Invalid email address.');
            }
        }

        // Zip Code (Loose 5 digit check if populated)
        if(zipInput.length && zipInput.val() !== '') {
            const zip = zipInput.val();
            if(!zip.match(/^\d{5}(?:[-\s]\d{4})?$/)) {
                valid = false;
                errors.push('Invalid Zip Code.');
            }
        }

        if(!valid) {
            e.preventDefault();
            alert(errors.join('\n'));
        }
        // If valid, allow default submission to admin_post.php
    });
});
