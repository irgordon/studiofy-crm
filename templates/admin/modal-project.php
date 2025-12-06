<div id="studiofy-modal-overlay" class="studiofy-hidden">
    <div class="studiofy-modal">
        <div class="studiofy-modal-header">
            <h2 id="studiofy-modal-title">Project Details</h2>
            <button id="studiofy-modal-close" type="button">&times;</button>
        </div>
        
        <div class="studiofy-modal-body">
            <div class="studiofy-milestone-list" id="studiofy-milestones-container">
                </div>

            <div class="studiofy-task-editor" id="studiofy-task-editor">
                <h3>Task Details</h3>
                <form id="studiofy-task-form">
                    <input type="hidden" id="task-id" name="id">
                    <input type="hidden" id="task-milestone-id" name="milestone_id">
                    <input type="hidden" id="task-status" name="status" value="pending">

                    <label>Task Title</label>
                    <input type="text" id="task-title" class="widefat" required placeholder="Enter task name...">

                    <label style="margin-top:10px; display:block;">Priority</label>
                    <select id="task-priority" class="widefat">
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                        <option value="Urgent">Urgent</option>
                    </select>

                    <label style="margin-top:10px; display:block;">Description</label>
                    <textarea id="task-desc" class="widefat" rows="5"></textarea>

                    <div class="studiofy-form-actions">
                        <button type="submit" class="button button-primary button-large">Save Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* modal-project specific styles to ensure grid layout */
.studiofy-modal-body { display: flex; height: 500px; padding: 0; }
.studiofy-milestone-list { width: 40%; border-right: 1px solid #ddd; padding: 20px; overflow-y: auto; background: #fafafa; }
.studiofy-task-editor { width: 60%; padding: 20px; overflow-y: auto; }
.studiofy-milestone-group h4 { margin-top: 0; margin-bottom: 10px; color: #555; text-transform: uppercase; font-size: 11px; }
.studiofy-task-list { margin: 0 0 20px 0; padding: 0; list-style: none; }
.studiofy-task-item { background: #fff; padding: 10px; border: 1px solid #e5e5e5; margin-bottom: 5px; cursor: pointer; border-radius: 3px; display: flex; justify-content: space-between; align-items: center; }
.studiofy-task-item:hover { border-color: #2271b1; }
.studiofy-add-task { color: #2271b1; cursor: pointer; font-size: 12px; margin-top: 5px; display: inline-block; }
.studiofy-add-task:hover { text-decoration: underline; }
.task-check { color: #ccc; cursor: pointer; margin-left: 5px; }
.task-check:hover { color: #46b450; }
</style>
