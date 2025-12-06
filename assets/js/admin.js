/**
 * Studiofy Admin Core
 * @package Studiofy
 * @version 2.2.18
 */
jQuery(document).ready(function($){
    
    // 1. Modal Logic
    function setupModal(triggerId, modalId) {
        $(triggerId).click(function(e){ e.preventDefault(); $(modalId).removeClass('studiofy-hidden'); });
        $(modalId + ' .close-modal').click(function(e){ e.preventDefault(); $(modalId).addClass('studiofy-hidden'); });
        $(modalId).click(function(e){ if(e.target === this) { $(this).addClass('studiofy-hidden'); } });
    }
    setupModal('#btn-new-customer', '#modal-new-customer');
    setupModal('#btn-new-appt', '#modal-new-appt');

    // 2. WP Color Picker
    if($.fn.wpColorPicker) $('.studiofy-color-field').wpColorPicker();
    
    // 3. Media Uploader
    $('.studiofy-upload-btn').click(function(e) {
        e.preventDefault();
        var button = $(this);
        var targetId = button.data('target');
        var custom_uploader = wp.media({ title: 'Select Image', button: { text: 'Use this image' }, multiple: false })
            .on('select', function() { $(targetId).val(custom_uploader.state().get('selection').first().toJSON().url); }).open();
    });

    $('.delete-link').click(function(){ return confirm('Are you sure?'); });

    // 4. Phone Auto-Format
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

    // 5. Google Maps Autocomplete
    function initAutocomplete() {
        const input = document.getElementById('addr_street');
        if (!input) return;

        const autocomplete = new google.maps.places.Autocomplete(input, {
            types: ['address'],
            componentRestrictions: { country: 'us' }, // Restrict to US per requirement
            fields: ['address_components', 'geometry']
        });

        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            let street = '', city = '', state = '', zip = '';

            for (const component of place.address_components) {
                const type = component.types[0];
                if (type === 'street_number') street = component.long_name + ' ' + street;
                if (type === 'route') street += component.long_name;
                if (type === 'locality') city = component.long_name;
                if (type === 'administrative_area_level_1') state = component.short_name;
                if (type === 'postal_code') zip = component.long_name;
            }

            // Populate fields
            $('#addr_street').val(street);
            $('#addr_city').val(city);
            $('#addr_state').val(state);
            $('#addr_zip').val(zip);
        });
    }
    
    // Init if Google Maps script is loaded
    if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
        initAutocomplete();
    }
});
