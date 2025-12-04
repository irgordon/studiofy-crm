jQuery(document).ready(function($) {
    $('#studiofy-fields-container').sortable({ update: serialize });
    $('#add-field').click(function() {
        $('#studiofy-fields-container').append('<div class="field-row" style="background:#eee;padding:10px;margin:5px;">Label: <input type="text" class="f-label"> Type: <select class="f-type"><option>text</option><option>textarea</option></select> <span class="dashicons dashicons-trash remove-f"></span></div>');
    });
    $(document).on('click', '.remove-f', function() { $(this).parent().remove(); serialize(); });
    $(document).on('keyup change', '.f-label, .f-type', serialize);
    function serialize() {
        var data = [];
        $('.field-row').each(function(i) {
            data.push({ id: i+1, label: $(this).find('.f-label').val(), type: $(this).find('.f-type').val() });
        });
        $('#studiofy_form_schema_input').val(JSON.stringify(data));
    }
});
