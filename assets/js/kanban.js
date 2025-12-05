/**
 * Studiofy Kanban Board
 * @package Studiofy
 * @version 2.0.4
 */
jQuery(document).ready(function($) {
    
    $(".studiofy-card-container").sortable({
        connectWith: ".studiofy-card-container",
        placeholder: "studiofy-card-placeholder",
        start: function(event, ui) {
            ui.item.addClass('dragging');
        },
        stop: function(event, ui) {
            ui.item.removeClass('dragging');
            
            var projectId = ui.item.data('id');
            var newStatus = ui.item.closest('.studiofy-column').data('status');

            $.ajax({
                url: studiofySettings.root + 'studiofy/v1/projects/update-status',
                method: 'POST',
                beforeSend: function ( xhr ) {
                    xhr.setRequestHeader( 'X-WP-Nonce', studiofySettings.nonce );
                },
                data: {
                    id: projectId,
                    status: newStatus
                },
                success: function(response) {
                    console.log('Status updated');
                },
                error: function(err) {
                    alert('Error updating status');
                    $(this).sortable('cancel');
                }
            });
        }
    }).disableSelection();

    window.StudiofyKanban = {
        editProject: function(id) {
            if(window.StudiofyModal) {
                window.StudiofyModal.open(id);
            } else {
                alert("Modal JS not loaded.");
            }
        }
    };
});
