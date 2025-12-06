/**
 * Studiofy Admin Core
 * @package Studiofy
 * @version 2.1.6
 */
jQuery(document).ready(function($){
    
    // 1. Modal Utilities
    function openModal(id) {
        $(id).show().removeClass('studiofy-hidden');
    }
    
    function closeModal(id) {
        $(id).hide().addClass('studiofy-hidden');
    }

    // Close buttons and overlay click
    $('.close-modal, .studiofy-modal-overlay').click(function(e) {
        if (e.target === this || $(e.target).hasClass('close-modal') || $(e.target).parent().hasClass('close-modal')) {
            e.preventDefault();
            $('.studiofy-modal-overlay').hide().addClass('studiofy-hidden');
        }
    });

    // 2. Add New Appointment Button
    $('#btn-new-appt').click(function(e){
        e.preventDefault();
        // Reset Form
        $('#booking-form')[0].reset();
        $('#booking_id').val('');
        $('#modal-title').text('New Appointment');
        $('#btn-save-booking').text('Create Appointment');
        openModal('#modal-new-appt');
    });

    // 3. Edit Appointment (Clicking on Calendar Event)
    $('.calendar-event').click(function() {
        var data = $(this).data('booking');
        if(!data) return;

        // Populate Form
        $('#booking_id').val(data.id);
        $('#booking_title').val(data.title);
        $('#booking_customer').val(data.customer_id);
        $('#booking_date').val(data.booking_date);
        $('#booking_time').val(data.booking_time); // Ensure format matches HH:MM:SS
        $('#booking_location').val(data.location);
        $('#booking_status').val(data.status);
        $('#booking_notes').val(data.notes);

        // Update UI Text
        $('#modal-title').text('Edit Appointment');
        $('#btn-save-booking').text('Update Appointment');
        
        openModal('#modal-new-appt');
    });

    // 4. Other Admin Utils
    if($.fn.wpColorPicker) {
        $('.studiofy-color-field').wpColorPicker();
    }
    
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

    $('.delete-link').click(function(){ return confirm('Are you sure you want to delete this item?'); });

    // Customer Form Validation
    $('#studiofy-customer-form').on('submit', function(e) {
        let valid = true;
        let errors = [];
        try {
            const phoneInput = $(this).find('input[name="phone"]');
            if(phoneInput.length > 0 && phoneInput.val().trim() !== '') {
                // Formatting logic is handled by 'input' event below, this just checks safety
            }
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

    // Phone Auto-Format
    $('#phone').on('input', function() {
        var input = $(this).val().replace(/\D/g, '');
        var formatted = '';
        if (input.length > 0) {
            if (input.length <= 3) formatted = input;
            else if (input.length <= 6) formatted = '(' + input.substring(0, 3) + ') ' + input.substring(3);
            else formatted = '(' + input.substring(0, 3) + ') ' + input.substring(3, 6) + '-' + input.substring(6, 10);
        }
        $(this).val(formatted);
    });
});
