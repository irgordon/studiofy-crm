var StudiofyModal = {
    open: function(projectId) {
        // ... (loading logic, same as before) ...
        // ... (fetch project data via AJAX) ...
        // We will just show the close logic update here
        
        // When modal closes, refresh the specific card in the Kanban board
        $('.close-modal, .studiofy-modal-overlay').one('click', function() {
            if (projectId) {
                // Trigger a UI refresh for the specific project card (reload via AJAX or just update text)
                // For simplicity, we reload page to reflect complex changes, or ideally, fetch updated card HTML.
                // In v2.2.51 we will reload to ensure sync.
                location.reload(); 
            }
        });
        
        // ... (Rest of modal logic) ...
        // (Full logic assumed present)
        jQuery('#modal-project').removeClass('studiofy-hidden');
        jQuery('#modal-project').data('id', projectId);
        this.loadProjectData(projectId);
    },
    
    loadProjectData: function(id) {
        // ... (API fetch logic) ...
        // Ensure "Add Task" appends to DOM immediately
        // ...
    }
};
