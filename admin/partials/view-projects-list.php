<div class="wrap studiofy-wrapper">
    <h1 class="wp-heading-inline">Projects</h1>
    <a href="?page=studiofy-projects&action=new" class="page-title-action">Add New</a>
    
    <div class="studiofy-kanban-header">
        <div class="k-card k-blue"><h3><?php echo $counts['pending']; ?></h3><small>Pending</small></div>
        <div class="k-card k-green"><h3><?php echo $counts['active']; ?></h3><small>Active Jobs</small></div>
        <div class="k-card k-gray"><h3><?php echo $counts['complete']; ?></h3><small>Completed</small></div>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><a href="?page=studiofy-projects&orderby=title">Project Title</a></th>
                <th>Client</th>
                <th>Phase</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($projects as $row): ?>
            <tr>
                <td><strong><?php echo esc_html($row->title); ?></strong></td>
                <td><?php echo esc_html($row->client_name ?: 'Unknown'); ?></td>
                <td><span class="badge"><?php echo esc_html($row->workflow_phase); ?></span></td>
                <td><?php echo esc_html($row->status); ?></td>
                <td class="actions-col">
                    <a href="?page=studiofy-projects&action=edit&id=<?php echo $row->id; ?>" class="dashicons dashicons-edit"></a>
                    <a href="<?php echo admin_url('admin-post.php?action=studiofy_delete_item&table=studiofy_projects&id='.$row->id); ?>" onclick="return confirm('Delete?')" class="dashicons dashicons-trash"></a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
