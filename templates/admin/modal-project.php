<div id="studiofy-modal-overlay" class="studiofy-hidden">
    <div class="studiofy-modal">
        <div class="studiofy-modal-header">
            <h2 id="studiofy-modal-title">Project Details</h2>
            <button id="studiofy-modal-close" type="button">&times;</button>
        </div>
        <div class="studiofy-modal-body">
            <div class="studiofy-milestone-list" id="studiofy-milestones-container"></div>
            <div class="studiofy-task-editor" id="studiofy-task-editor">
                <h3>Task Details</h3>
                <form id="studiofy-task-form">
                    <input type="hidden" id="task-id" name="id">
                    <input type="hidden" id="task-milestone-id" name="milestone_id">
                    <label>Title</label><input type="text" id="task-title" class="widefat" required>
                    <label>Priority</label>
                    <select id="task-priority" class="widefat">
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                    </select>
                    <label>Description</label><textarea id="task-desc" class="widefat" rows="3"></textarea>
                    <div class="studiofy-form-actions">
                        <button type="submit" class="button button-primary">Save Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
