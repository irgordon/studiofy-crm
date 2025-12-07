/**
 * Studiofy Kanban Board
 * @version 2.2.51
 */
jQuery(document).ready(function($) {
    // Drag & Drop
    $('.studiofy-card-container').sortable({
        connectWith: '.studiofy-card-container',
        placeholder: 'card-placeholder',
        dropOnEmpty: true,
        update: function(event, ui) {
            // Only fire update once per move (sender side)
            if (this === ui.item.parent()[0]) {
                const card = ui.item;
                const newStatus = card.closest('.studiofy-column').data('status');
                const projectId = card.data('id');
                
                // Update Server
                wp.apiFetch({
                    path: '/studiofy/v1/projects/' + projectId,
                    method: 'POST',
                    data: { status: newStatus },
                    headers: { 'X-WP-Nonce': studiofySettings.nonce }
                }).then(() => {
                    // Update Counts
                    updateColumnCounts();
                });
            }
        }
    }).disableSelection();

    function updateColumnCounts() {
        $('.studiofy-column').each(function() {
            const count = $(this).find('.studiofy-card').length;
            $(this).find('.col-count').text(count);
        });
    }

    // Inline Task Deletion
    $(document).on('click', '.btn-delete-task-inline', function(e) {
        e.stopPropagation(); // Don't drag
        e.preventDefault();
        
        const btn = $(this);
        const taskItem = btn.closest('.task-item');
        const taskId = taskItem.data('task-id');

        if(!confirm('Delete this task?')) return;

        btn.css('opacity', '0.5');

        $.post(studiofySettings.ajax_url, {
            action: 'studiofy_delete_task_ajax',
            nonce: studiofySettings.nonce,
            task_id: taskId
        }, function(res) {
            if(res.success) {
                taskItem.fadeOut(300, function(){ $(this).remove(); });
            } else {
                alert('Error deleting task');
                btn.css('opacity', '1');
            }
        });
    });
});
