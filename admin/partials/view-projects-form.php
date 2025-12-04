<div class="wrap">
    <h1><?php echo $project ? 'Edit Project' : 'New Project'; ?></h1>
    <form method="post" action="admin-post.php">
        <input type="hidden" name="action" value="studiofy_save_project">
        <?php if($project) echo '<input type="hidden" name="id" value="'.$project->id.'">'; ?>
        <?php wp_nonce_field('save_project'); ?>

        <table class="form-table">
            <tr><th>Title</th><td><input type="text" name="title" value="<?php echo esc_attr($project->title ?? ''); ?>" class="regular-text" required></td></tr>
            <tr><th>Client</th><td>
                <select name="client_id">
                    <option value="">-- Select Client --</option>
                    <?php foreach($clients as $c): ?>
                        <option value="<?php echo $c->id; ?>" <?php selected($project->client_id ?? 0, $c->id); ?>><?php echo esc_html($c->name); ?></option>
                    <?php endforeach; ?>
                </select>
                <a href="admin.php?page=studiofy-crm-new">Add Client</a>
            </td></tr>
            <tr><th>Phase</th><td>
                <select name="workflow_phase">
                    <?php foreach(['New','Inquiry','In Progress','Proof','Revisions','Delivered'] as $ph): ?>
                        <option <?php selected($project->workflow_phase ?? '', $ph); ?>><?php echo $ph; ?></option>
                    <?php endforeach; ?>
                </select>
            </td></tr>
            <tr><th>Status</th><td>
                <select name="status">
                    <?php foreach(['New','Pending Invoice','Deposit Paid','In Progress','Complete','Cancelled'] as $st): ?>
                        <option <?php selected($project->status ?? '', $st); ?>><?php echo $st; ?></option>
                    <?php endforeach; ?>
                </select>
            </td></tr>
            <tr><th>Notes</th><td><textarea name="notes" rows="5" class="large-text"><?php echo esc_textarea($project->notes ?? ''); ?></textarea></td></tr>
        </table>
        <?php submit_button('Save Project'); ?>
    </form>
</div>
