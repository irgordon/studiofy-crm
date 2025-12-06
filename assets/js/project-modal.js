/**
 * Studiofy Project Modal
 * @package Studiofy
 * @version 2.2.11
 */
jQuery(document).ready(function($) {
    
    const ApiRoot = studiofySettings.root + 'studiofy/v1/';
    let currentProjectId = 0;
    
    window.StudiofyModal = {
        open: function(id) {
            currentProjectId = id;
            $('#studiofy-modal-overlay').removeClass('studiofy-hidden');
            $('#studiofy-modal-title').text('Project #' + id + ' Details');
            
            // Reset Form
            $('#studiofy-task-form')[0].reset();
            $('#task-id').val('');
            $('#task-milestone-id').data('project-id', id);

            loadProjectDetails(id);
        }
    };

    $('#studiofy-modal-close').click(function() {
        $('#studiofy-modal-overlay').addClass('studiofy-hidden');
    });

    function loadProjectDetails(id) {
        $('#studiofy-milestones-container').html('<p>Loading...</p>');
        
        wp.apiFetch({
            path: '/studiofy/v1/projects/' + id + '/details',
            headers: { 'X-WP-Nonce': studiofySettings.nonce }
        }).then(milestones => {
            renderMilestones(milestones);
            if(milestones.length > 0) {
                $('#task-milestone-id').val(milestones[0].id);
            }
        }).catch(err => {
            console.error(err);
            $('#studiofy-milestones-container').html('<p style="color:red">Error loading details.</p>');
        });
    }

    function renderMilestones(milestones) {
        const container = $('#studiofy-milestones-container');
        container.empty();

        if (milestones.length === 0) {
            container.html('<p>No milestones found. Add a task to get started.</p>');
            return;
        }

        milestones.forEach(m => {
            let html = `<div class="studiofy-milestone-group">`;
            html += `<h4>${m.name}</h4>`;
            html += `<ul class="studiofy-task-list">`;
            
            m.tasks.forEach(t => {
                const isComplete = t.status === 'completed';
                const style = isComplete ? 'text-decoration: line-through; opacity: 0.7;' : '';
                
                html += `<li class="studiofy-task-item" data-task='${JSON.stringify(t)}' style="${style}">
                            <span class="task-title">${t.title}</span> 
                            <div>
                                <span class="studiofy-badge ${t.priority.toLowerCase()}">${t.priority}</span>
                                <span class="dashicons dashicons-yes task-check" title="Mark Complete" data-id="${t.id}" data-status="${t.status}"></span>
                            </div>
                         </li>`;
            });
            
            html += `<li class="studiofy-add-task" data-milestone="${m.id}">+ Add Task</li>`;
            html += `</ul></div>`;
            container.append(html);
        });
    }

    // 1. Click to Edit Task
    $(document).on('click', '.studiofy-task-item', function(e) {
        if ($(e.target).hasClass('task-check')) return; // Don't trigger if clicking checkbox
        
        const task = $(this).data('task');
        $('#task-id').val(task.id);
        $('#task-milestone-id').val(task.milestone_id);
        $('#task-title').val(task.title);
        $('#task-priority').val(task.priority);
        $('#task-desc').val(task.description);
        $('#task-status').val(task.status);
    });

    // 2. Click "+ Add Task"
    $(document).on('click', '.studiofy-add-task', function() {
        const mid = $(this).data('milestone');
        $('#studiofy-task-form')[0].reset();
        $('#task-id').val('');
        $('#task-milestone-id').val(mid);
        $('#task-title').focus();
    });

    // 3. Mark Complete
    $(document).on('click', '.task-check', function(e) {
        e.stopPropagation();
        const id = $(this).data('id');
        const currentStatus = $(this).data('status');
        const newStatus = currentStatus === 'completed' ? 'pending' : 'completed';
        const taskData = $(this).closest('li').data('task');
        
        // Optimistic UI Update
        const li = $(this).closest('li');
        if (newStatus === 'completed') li.css({ 'text-decoration': 'line-through', 'opacity': '0.7' });
        else li.css({ 'text-decoration': 'none', 'opacity': '1' });

        wp.apiFetch({
            path: '/studiofy/v1/tasks',
            method: 'POST',
            headers: { 'X-WP-Nonce': studiofySettings.nonce },
            data: {
                id: id,
                status: newStatus,
                title: taskData.title, 
                milestone_id: taskData.milestone_id 
            }
        }).then(res => {
            taskData.status = newStatus;
            li.data('task', taskData);
            $(this).data('status', newStatus);
        }).catch(err => {
            alert('Error updating task.');
            loadProjectDetails(currentProjectId);
        });
    });

    // 4. Submit Form
    $('#studiofy-task-form').on('submit', function(e) {
        e.preventDefault(); // FIXED: Prevents page reload
        const btn = $(this).find('button[type="submit"]');
        const originalText = btn.text();
        btn.text('Saving...').prop('disabled', true);

        const data = {
            id: $('#task-id').val(),
            milestone_id: $('#task-milestone-id').val(),
            project_id: currentProjectId, // Fallback for default milestone creation
            title: $('#task-title').val(),
            priority: $('#task-priority').val(),
            description: $('#task-desc').val(),
            status: $('#task-status').val() || 'pending'
        };

        wp.apiFetch({
            path: '/studiofy/v1/tasks',
            method: 'POST',
            headers: { 'X-WP-Nonce': studiofySettings.nonce },
            data: data
        }).then(response => {
            btn.text(originalText).prop('disabled', false);
            $('#studiofy-task-form')[0].reset();
            $('#task-id').val('');
            loadProjectDetails(currentProjectId);
        }).catch(err => {
            console.error(err);
            alert('Error saving task. Check console.');
            btn.text(originalText).prop('disabled', false);
        });
    });
});
