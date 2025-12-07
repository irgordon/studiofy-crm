/**
 * Studiofy Project Modal
 * @version 2.2.54
 */
(function($) {
    // Attach to global window object so onclick="..." can find it
    window.StudiofyModal = {
        open: function(projectId) {
            var $modal = $('#modal-project');
            
            if ($modal.length === 0) {
                console.error('Studiofy: #modal-project element not found in DOM.');
                return;
            }

            $modal.removeClass('studiofy-hidden');
            $modal.data('id', projectId);
            
            this.loadProjectData(projectId);
            
            // Setup Close Handlers (One-time binding)
            $('.close-modal, .studiofy-modal-overlay').off('click').on('click', function(e) {
                if (e.target === this || $(this).hasClass('close-modal')) {
                    $('#modal-project').addClass('studiofy-hidden');
                    // Refresh page to update Kanban card counts/status
                    location.reload(); 
                }
            });
        },

        loadProjectData: function(id) {
            var $content = $('#modal-project-content');
            $content.html('<p style="padding:20px; text-align:center;">Loading...</p>');

            wp.apiFetch({ path: '/studiofy/v1/projects/' + id }).then(data => {
                this.renderContent(data);
            }).catch(err => {
                $content.html('<p class="error" style="color:red; padding:20px;">Error loading project data. Check console.</p>');
                console.error(err);
            });
        },

        renderContent: function(data) {
            var html = `
                <div style="margin-bottom:15px;">
                    <h3 style="margin-top:0;">${data.title}</h3>
                    <p class="description">Manage tasks for this project.</p>
                </div>
                <div class="task-list">
                    <ul id="modal-task-list" style="list-style:none; padding:0; margin:0 0 15px 0; border:1px solid #ddd; border-radius:4px; max-height:300px; overflow-y:auto;">`;
            
            if (data.tasks && data.tasks.length > 0) {
                data.tasks.forEach(function(t) {
                    var checked = t.status === 'completed' ? 'checked' : '';
                    var strike = t.status === 'completed' ? 'style="text-decoration:line-through; opacity:0.6;"' : '';
                    html += `
                        <li style="padding:10px; border-bottom:1px solid #eee; display:flex; align-items:center;">
                            <label ${strike} style="flex:1; cursor:pointer;">
                                <input type="checkbox" class="task-checkbox" data-id="${t.id}" ${checked}> 
                                <span style="margin-left:8px;">${t.title}</span>
                            </label>
                        </li>`;
                });
            } else {
                html += `<li style="padding:15px; color:#777; text-align:center;">No tasks found. Add one below.</li>`;
            }
            
            html += `</ul>
                    <div class="add-task-row" style="display:flex; gap:10px;">
                        <input type="text" id="new-task-title" placeholder="New Task Title..." class="widefat" style="flex:1;">
                        <button class="button button-primary" id="btn-add-task">Add Task</button>
                    </div>
                </div>`;

            $('#modal-project-content').html(html);

            // Bind Checkbox Events
            $('.task-checkbox').change(function() {
                var id = $(this).data('id');
                var status = this.checked ? 'completed' : 'pending';
                var label = $(this).closest('label');
                
                if(status === 'completed') label.css({textDecoration:'line-through', opacity:0.6});
                else label.css({textDecoration:'none', opacity:1});

                wp.apiFetch({
                    path: '/studiofy/v1/tasks/' + id,
                    method: 'POST',
                    data: { status: status }
                });
            });

            // Bind Add Task Event
            $('#btn-add-task').click(function() {
                var title = $('#new-task-title').val();
                if (!title) {
                    alert('Please enter a task title.');
                    return;
                }
                
                var $btn = $(this);
                $btn.prop('disabled', true).text('Adding...');
                
                wp.apiFetch({
                    path: '/studiofy/v1/projects/' + data.id + '/tasks',
                    method: 'POST',
                    data: { title: title }
                }).then(res => {
                    // Re-fetch project data to refresh list cleanly (easier than appending HTML manually)
                    window.StudiofyModal.loadProjectData(data.id);
                }).catch(err => {
                    alert('Error adding task.');
                    $btn.prop('disabled', false).text('Add Task');
                });
            });
        }
    };
})(jQuery);
