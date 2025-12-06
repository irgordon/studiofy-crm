/**
 * Studiofy Admin Core
 * @package Studiofy
 * @version 2.1.4
 */
jQuery(document).ready(function($){
    
    // Modal Logic
    function setupModal(triggerId, modalId) {
        $(triggerId).click(function(e){ 
            e.preventDefault(); 
            $(modalId).show(); // Force show
            $(modalId).removeClass('studiofy-hidden'); 
        });
        
        $(modalId + ' .close-modal').click(function(e){ 
            e.preventDefault(); 
            $(modalId).addClass('studiofy-hidden'); 
            $(modalId).hide(); // Force hide
        });
        
        // Close on overlay click
        $(modalId).click(function(e){
            if(e.target === this) {
                $(this).addClass('studiofy-hidden');
                $(this).hide();
            }
        });
    }
    
    setupModal('#btn-new-customer', '#modal-new-customer'); // (Should check if element exists on page)
    setupModal('#btn-new-appt', '#modal-new-appt');

    // WP Color Picker
    if($.fn.wpColorPicker) {
        $('.studiofy-color-field').wpColorPicker();
    }
    
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

    // Customer Form Validation (Only runs if form exists)
    $('#studiofy-customer-form').on('submit', function(e) {
        let valid = true;
        let errors = [];

        try {
            // Phone Validation
            const phoneInput = $(this).find('input[name="phone"]');
            if(phoneInput.length > 0 && phoneInput.val().trim() !== '') {
                const phone = phoneInput.val();
                if(!phone.match(/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im)) {
                    // console.warn('Phone format warning');
                }
            }

            // Email Validation
            const emailInput = $(this).find('input[name="email"]');
            if(emailInput.length > 0) {
                const email = emailInput.val();
                if(email.trim() === '' || !email.match(/^[^@]+@[^@]+\.[a-zA-Z]{2,}$/)) {
                    valid = false;
                    errors.push('A valid email address is required.');
                }
            }

            if(!valid) {
                e.preventDefault();
                alert(errors.join('\n'));
            }

        } catch (err) {
            console.error('Validation Script Error:', err);
        }
    });
});
