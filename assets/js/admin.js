/**
 * Studiofy Admin Core
 * @package Studiofy
 * @version 2.1.1
 */
jQuery(document).ready(function($){
    
    // 1. WP Color Picker
    $('.studiofy-color-field').wpColorPicker();
    
    // 2. Media Uploader
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

    // 3. Delete Confirmation
    $('.delete-link').click(function(){ return confirm('Are you sure you want to delete this item?'); });

    // 4. Phone Auto-Formatter (US Format)
    // Listens for input on any field with ID "phone"
    $('#phone').on('input', function() {
        var input = $(this).val().replace(/\D/g, ''); // Strip non-digits
        var formatted = '';
        
        if (input.length > 0) {
            // (123
            if (input.length <= 3) {
                formatted = input;
            } 
            // (123) 456
            else if (input.length <= 6) {
                formatted = '(' + input.substring(0, 3) + ') ' + input.substring(3);
            } 
            // (123) 456-7890
            else {
                formatted = '(' + input.substring(0, 3) + ') ' + input.substring(3, 6) + '-' + input.substring(6, 10);
            }
        }
        
        $(this).val(formatted);
    });

    // 5. Email Visual Feedback
    $('#email').on('blur', function() {
        var email = $(this).val();
        var errorField = $('#email-error');
        var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/; // Basic email regex

        if(email.length > 0 && !regex.test(email)) {
            $(this).css('border-color', '#d63638');
            errorField.text('Please enter a valid email address.');
            errorField.show();
        } else {
            $(this).css('border-color', ''); // Reset
            errorField.hide();
        }
    });
});
