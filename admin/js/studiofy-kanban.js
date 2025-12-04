jQuery(document).ready(function($){
    $('.kanban-dropzone').sortable({
        connectWith: '.kanban-dropzone', placeholder: 'kanban-placeholder',
        receive: function(e, ui) {
            $.post(studiofy_vars.ajax_url, {
                action: 'studiofy_update_kanban_status',
                security: studiofy_vars.kanban_nonce,
                post_id: ui.item.data('id'),
                phase: ui.item.closest('.kanban-column').data('phase')
            });
        }
    }).disableSelection();
});
