/**
 * Studiofy Project Modal
 * @package Studiofy
 * @version 2.0.4
 */
jQuery(document).ready(function($) {
    const ApiRoot = studiofySettings.root + 'studiofy/v1/';
    
    window.StudiofyModal = {
        open: function(id) {
            $('#studiofy-modal-overlay').removeClass('studiofy-hidden');
            $('#studiofy-modal-title').text('Project #' + id + ' Details');
            loadProjectDetails(id);
        }
    };

    $('#studiofy-modal-close').click(function() {
        $('#studiofy-modal-overlay').addClass('studiofy-hidden');
    });

    function loadProjectDetails(id) {
        $.ajax({
            url: ApiRoot + 'projects/' + id + '/details',
            beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', studiofySettings.nonce); },
            success: function(milestones) {
                renderMilestones(milestones);
            }
        });
    }

    function renderMilestones(milestones) {
        const container = $('#studiofy-milestones-container');
        container.empty();

        if (milestones.length === 0) {
            container.html('<p>No milestones found.</p>');
            return;
        }

        milestones.forEach(m => {
            let html = `<div class="studiofy-milestone-group">`;
            html += `<h4>${m.name}</h4>`;
            html += `<ul class="studiofy-task-list">`;
            
            m.tasks.forEach(t => {
                html += `<li class="studiofy-task-item" data-task='${JSON.stringify(t)}'>
                            ${t.title} <span class="studiofy-badge ${t.priority.toLowerCase()}">${t.priority}</span>
                         </li>`;
            });
            
            html += `<li class="studiofy-add-task" data-milestone="${m.id}">+ Add Task</li>`;
            html += `</ul></div>`;
            container.append(html);
        });
    }
});
