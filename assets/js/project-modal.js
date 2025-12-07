/**
 * Studiofy Project Modal
 * @version 2.2.55
 */
(function($) {
    window.StudiofyModal = {
        open: function(projectId) {
            var $modal = $('#modal-project');
            $modal.removeClass('studiofy-hidden').data('id', projectId);
            this.loadProjectData(projectId);
            $('.close-modal, .studiofy-modal-overlay').off('click').on('click', function(e) {
                if (e.target === this || $(this).hasClass('close-modal')) {
                    $('#modal-project').addClass('studiofy-hidden');
                    location.reload(); 
                }
            });
        },

        loadProjectData: function(id) {
            $('#modal-project-content').html('<p style="padding:20px; text-align:center;">Loading...</p>');
            wp.apiFetch({ path: '/studiofy/v1/projects/' + id }).then(data => {
                this.renderContent(data);
            }).catch(err => console.error(err));
        },

        renderContent: function(data) {
            // Build User Options
            let userOpts = '<option value="0">Unassigned</option>';
            if (studiofySettings.users) {
                studiofySettings.users.forEach(u => {
                    userOpts += `<option value="${u.id}">${u.name}</option>`;
                });
            }

            var html = `
                <div style="margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;">
                    <h2 style="margin:0;">${data.title}</h2>
                </div>
                
                <div class="studiofy-add-task-box" style="background:#f6f7f7; padding:15px; border-radius:4px; margin-bottom:20px;">
                    <h4 style="margin-top:0;">Add New Task</h4>
                    <div style="display:flex; gap:10px; margin-bottom:10px;">
                        <input type="text" id="new-task-title" placeholder="Task Title" class="widefat" style="flex:2;">
                        <select id="new-task-group" class="widefat" style="flex:1;">
                            <option value="General">General</option>
                            <option value="Editing">Editing</option>
                            <option value="Shooting">Shooting</option>
                            <option value="Admin">Admin</option>
                        </select>
                        <select id="new-task-priority" class="widefat" style="flex:1;">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                            <option value="Urgent">Urgent</option>
                        </select>
                    </div>
                    <div style="display:flex; gap:10px; margin-bottom:10px;">
                        <select id="new-task-assignee" class="widefat" style="flex:1;">${userOpts}</select>
                        <input type="date" id="new-task-start" class="widefat" style="flex:1;" placeholder="Start">
                        <input type="date" id="new-task-due" class="widefat" style="flex:1;" placeholder="Due">
                        <button class="button button-primary" id="btn-add-task" style="flex:0 0 80px;">Add</button>
                    </div>
                </div>

                <div class="task-grid-container">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th width="30"></th>
                                <th>Task</th>
                                <th>Group</th>
                                <th>Assignee</th>
                                <th>Priority</th>
                                <th>Timeline</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="modal-task-list">`;

            if (data.tasks && data.tasks.length > 0) {
                data.tasks.forEach(function(t) {
                    html += window.StudiofyModal.renderTaskRow(t, userOpts);
                });
            } else {
                html += `<tr><td colspan="7" style="text-align:center;">No tasks found.</td></tr>`;
            }
            
            html += `</tbody></table></div>`;

            $('#modal-project-content').html(html);

            // Bind Events
            this.bindEvents(data.id);
        },

        renderTaskRow: function(t, userOpts) {
            // Calculate Timeline
            let timeline = '-';
            if (t.start_date && t.due_date) {
                const start = new Date(t.start_date);
                const due = new Date(t.due_date);
                const diffTime = Math.abs(due - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
                timeline = diffDays + ' Days';
            }

            // Assignee Name
            let assigneeName = 'Unassigned';
            if(studiofySettings.users && t.assignee_id) {
                let user = studiofySettings.users.find(u => u.id == t.assignee_id);
                if(user) assigneeName = user.name;
            }

            let statusBadge = `<span class="studiofy-badge ${t.status}">${t.status}</span>`;
            let checked = t.status === 'completed' ? 'checked' : '';

            return `
                <tr>
                    <td><input type="checkbox" class="task-checkbox" data-id="${t.id}" ${checked}></td>
                    <td><strong>${t.title}</strong></td>
                    <td>${t.group_name || '-'}</td>
                    <td>${assigneeName}</td>
                    <td>${t.priority}</td>
                    <td>${timeline}</td>
                    <td>${statusBadge}</td>
                </tr>`;
        },

        bindEvents: function(projectId) {
            // Add Task
            $('#btn-add-task').click(function() {
                var title = $('#new-task-title').val();
                if (!title) return;
                
                var payload = {
                    title: title,
                    group: $('#new-task-group').val(),
                    priority: $('#new-task-priority').val(),
                    assignee: $('#new-task-assignee').val(),
                    start_date: $('#new-task-start').val(),
                    due_date: $('#new-task-due').val(),
                    status: 'created'
                };

                $(this).prop('disabled', true).text('...');
                
                wp.apiFetch({
                    path: '/studiofy/v1/projects/' + projectId + '/tasks',
                    method: 'POST',
                    data: payload
                }).then(res => {
                    // Re-render whole content to simplify appending complex row
                    window.StudiofyModal.loadProjectData(projectId);
                });
            });

            // Toggle Status
            $('.task-checkbox').change(function() {
                var id = $(this).data('id');
                var status = this.checked ? 'completed' : 'inprogress';
                wp.apiFetch({ path: '/studiofy/v1/tasks/' + id, method: 'POST', data: { status: status } });
            });
        }
    };
})(jQuery);
