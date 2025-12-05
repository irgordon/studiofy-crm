/**
 * Studiofy Admin Core
 * @package Studiofy
 * @version 2.0.4
 */
jQuery(document).ready(function($){
    // Color Picker
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
});
