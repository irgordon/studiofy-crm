/**
 * Studiofy Project Modal
 * @version 2.2.52
 */
var StudiofyModal = {
    open: function(projectId) {
        // Use jQuery explicitly
        jQuery('#modal-project').removeClass('studiofy-hidden');
        jQuery('#modal-project').data('id', projectId);
        
        this.loadProjectData(projectId);
        
        // Setup Close Handlers
        jQuery('.close-modal, .studiofy-modal-overlay').off('click').on('click', function(e) {
            if (e.target === this || jQuery(this).hasClass('close-modal')) {
                jQuery('#modal-project').addClass('studiofy-hidden');
                // Refresh to update Kanban/List
                location.reload(); 
            }
        });
    },

    loadProjectData: function(id) {
        var $content = jQuery('#modal-project-content');
        $content.html('<p>Loading...</p>');

        wp.apiFetch({ path: '/studiofy/v1/projects/' + id }).then(data => {
            this.renderContent(data);
        }).catch(err => {
            $content.html('<p class="error">Error loading project.</p>');
        });
    },

    renderContent: function(data) {
        var html = `
            <h3>${data.title}</h3>
            <div class="task-list">
                <h4>Tasks</h4>
                <ul id="modal-task-list">`;
        
        if (data.tasks && data.tasks.length > 0) {
            data.tasks.forEach(function(t) {
                var checked = t.status === 'completed' ? 'checked' : '';
                var strike = t.status === 'completed' ? 'style="text-decoration:line-through; opacity:0.6;"' : '';
                html += `
                    <li>
                        <label ${strike}>
                            <input type="checkbox" class="task-checkbox" data-id="${t.id}" ${checked}> 
                            ${t.title}
                        </label>
                    </li>`;
            });
        } else {
            html += `<li>No tasks found.</li>`;
        }
        
        html += `</ul>
                <div class="add-task-row" style="margin-top:15px; display:flex; gap:10px;">
                    <input type="text" id="new-task-title" placeholder="New Task..." class="widefat">
                    <button class="button" id="btn-add-task">Add</button>
                </div>
            </div>`;

        jQuery('#modal-project-content').html(html);

        // Bind Checkbox Events
        jQuery('.task-checkbox').change(function() {
            var id = jQuery(this).data('id');
            var status = this.checked ? 'completed' : 'pending';
            var label = jQuery(this).closest('label');
            
            if(status === 'completed') label.css({textDecoration:'line-through', opacity:0.6});
            else label.css({textDecoration:'none', opacity:1});

            wp.apiFetch({
                path: '/studiofy/v1/tasks/' + id,
                method: 'POST',
                data: { status: status }
            });
        });

        // Bind Add Task Event
        jQuery('#btn-add-task').click(function() {
            var title = jQuery('#new-task-title').val();
            if (!title) return;
            
            jQuery(this).prop('disabled', true).text('Adding...');
            
            wp.apiFetch({
                path: '/studiofy/v1/projects/' + data.id + '/tasks',
                method: 'POST',
                data: { title: title }
            }).then(res => {
                // Refresh list visually without full reload
                var li = `<li><label><input type="checkbox" class="task-checkbox" data-id="${res.id}"> ${res.title}</label></li>`;
                if(jQuery('#modal-task-list li:first').text() === 'No tasks found.') {
                    jQuery('#modal-task-list').html(li);
                } else {
                    jQuery('#modal-task-list').append(li);
                }
                jQuery('#new-task-title').val('');
                jQuery('#btn-add-task').prop('disabled', false).text('Add');
            });
        });
    }
};
