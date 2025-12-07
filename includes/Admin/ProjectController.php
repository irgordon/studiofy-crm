<?php
/**
 * Project Controller
 * @package Studiofy\Admin
 * @version 2.2.51
 */

declare(strict_types=1);

namespace Studiofy\Admin;

use function Studiofy\studiofy_get_asset_version;
use Studiofy\Utils\TableHelper;

class ProjectController {
    use TableHelper;

    public function init(): void {
        add_action('admin_post_studiofy_save_project', [$this, 'handle_save']);
        add_action('admin_post_studiofy_delete_project', [$this, 'handle_delete']);
        add_action('admin_post_studiofy_bulk_project', [$this, 'handle_bulk']);
        // New: AJAX handler for quick task delete from Kanban
        add_action('wp_ajax_studiofy_delete_task_ajax', [$this, 'handle_delete_task_ajax']);
    }

    public function render_page(): void {
        $action = $_GET['action'] ?? 'list';
        if ($action === 'new' || $action === 'edit') {
            $this->render_form();
        } else {
            $this->render_dashboard();
        }
    }

    public function render_dashboard(): void {
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('studiofy-kanban', STUDIOFY_URL . 'assets/js/kanban.js', ['jquery', 'jquery-ui-sortable', 'wp-api-fetch'], studiofy_get_asset_version('assets/js/kanban.js'), true);
        wp_enqueue_script('studiofy-modal-js', STUDIOFY_URL . 'assets/js/project-modal.js', ['jquery', 'wp-api-fetch'], studiofy_get_asset_version('assets/js/project-modal.js'), true);
        wp_enqueue_style('studiofy-modal-css', STUDIOFY_URL . 'assets/css/modal.css', [], studiofy_get_asset_version('assets/css/modal.css'));

        wp_localize_script('studiofy-kanban', 'studiofySettings', [
            'root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
            'ajax_url' => admin_url('admin-ajax.php') // Needed for new delete action
        ]);
        
        global $wpdb;
        $table = $wpdb->prefix . 'studiofy_projects';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Projects</h1>
            <a href="?page=studiofy-projects&action=new" class="page-title-action">New Project</a>
            <hr class="wp-header-end">

            <?php if ($count == 0): ?>
                <div class="studiofy-empty-card">
                    <div class="empty-icon dashicons dashicons-grid-view"></div>
                    <h2>No projects yet</h2>
                    <p>Create your first project to start tracking work.</p>
                    <a href="?page=studiofy-projects&action=new" class="button button-primary button-large">Create Project</a>
                </div>
            <?php else: ?>
                <h2 class="nav-tab-wrapper"><span class="nav-tab nav-tab-active">Kanban Board</span></h2>
                <div style="margin-top: 20px;">
                    <?php $this->render_kanban_html(); ?>
                </div>
                <h2 class="nav-tab-wrapper" style="margin-top: 40px;"><span class="nav-tab nav-tab-active">Project List</span></h2>
                <div style="margin-top: 20px;">
                    <?php $this->render_list_html(); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        require_once STUDIOFY_PATH . 'templates/admin/modal-project.php';
    }

    private function render_kanban_html(): void {
        $projects = $this->get_projects_by_status();
        global $wpdb;

        $columns = [
            'todo' => ['label' => 'To Do', 'color' => 'col-gray'],
            'in_progress' => ['label' => 'In Progress', 'color' => 'col-blue'],
            'future' => ['label' => 'Future / On Hold', 'color' => 'col-green']
        ];
        ?>
        <div class="studiofy-kanban-board">
            <?php foreach($columns as $key => $col): ?>
            <div class="studiofy-column <?php echo esc_attr($col['color']); ?>" data-status="<?php echo esc_attr($key); ?>">
                <div class="studiofy-col-header">
                    <span class="col-name"><?php echo esc_html($col['label']); ?></span>
                    <span class="col-count"><?php echo count($projects[$key]); ?></span>
                </div>
                
                <div class="studiofy-card-container">
                    <?php foreach ($projects[$key] as $project): 
                        // Fetch tasks (Prioritize Urgent/High)
                        $tasks = $wpdb->get_results($wpdb->prepare(
                            "SELECT t.* FROM {$wpdb->prefix}studiofy_tasks t 
                             JOIN {$wpdb->prefix}studiofy_milestones m ON t.milestone_id = m.id 
                             WHERE m.project_id = %d AND t.status != 'completed'
                             ORDER BY CASE WHEN t.priority = 'Urgent' THEN 1 WHEN t.priority = 'High' THEN 2 ELSE 3 END, t.created_at DESC 
                             LIMIT 3", 
                            $project->id
                        ));
                    ?>
                        <div class="studiofy-card" data-id="<?php echo esc_attr($project->id); ?>">
                            <div class="studiofy-card-header">
                                <strong><?php echo esc_html($project->title); ?></strong>
                            </div>
                            <div class="studiofy-card-meta">
                                <div class="meta-item">
                                    <span class="dashicons dashicons-money-alt"></span>
                                    <?php echo esc_html($project->budget ? '$'.number_format((float)$project->budget, 0) : '-'); ?>
                                </div>
                            </div>
                            
                            <div class="studiofy-card-tasks">
                                <?php if (!empty($tasks)): ?>
                                    <ul class="task-preview-list">
                                        <?php foreach ($tasks as $t): 
                                            // Highlight Proofing Tasks
                                            $is_proof = (strpos($t->title, 'Proof') !== false) || (strpos($t->title, 'Approved') !== false);
                                            $style = $is_proof ? 'style="color: #d63638; font-weight: bold;"' : '';
                                        ?>
                                            <li <?php echo $style; ?> class="task-item" data-task-id="<?php echo $t->id; ?>">
                                                <span class="task-title-text">
                                                    <span class="dashicons dashicons-minus" style="font-size:12px; width:12px; height:12px; line-height:1.2;"></span> 
                                                    <?php echo esc_html($t->title); ?>
                                                </span>
                                                <button type="button" class="btn-delete-task-inline" title="Delete Task" aria-label="Delete">
                                                    <span class="dashicons dashicons-trash"></span>
                                                </button>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p style="font-size:11px; color:#999; margin:5px 0;">No pending tasks.</p>
                                <?php endif; ?>
                            </div>

                            <div class="studiofy-card-actions">
                                <button class="button button-small" onclick="StudiofyModal.open(<?php echo $project->id; ?>)">Manage</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <style>
            .task-preview-list { margin: 10px 0; padding: 0; list-style: none; font-size: 11px; color: #50575e; border-top: 1px solid #f0f0f1; padding-top: 5px; }
            .task-preview-list li { margin-bottom: 3px; display: flex; justify-content: space-between; align-items: center; }
            .task-title-text { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 85%; }
            .btn-delete-task-inline { background: none; border: none; padding: 0; cursor: pointer; color: #d63638; visibility: hidden; opacity: 0.6; }
            .task-preview-list li:hover .btn-delete-task-inline { visibility: visible; }
            .btn-delete-task-inline:hover { opacity: 1; }
            .btn-delete-task-inline .dashicons { font-size: 14px; width: 14px; height: 14px; }
        </style>
        <?php
    }

    // New AJAX Handler for Inline Delete
    public function handle_delete_task_ajax(): void {
        check_ajax_referer('wp_rest', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        
        $task_id = (int)$_POST['task_id'];
        global $wpdb;
        $wpdb->delete($wpdb->prefix.'studiofy_tasks', ['id' => $task_id]);
        wp_send_json_success(['message' => 'Task deleted']);
    }

    private function render_list_html(): void {
        global $wpdb;
        $orderby = $_GET['orderby'] ?? 'id';
        $order = strtoupper($_GET['order'] ?? 'DESC');
        $sql = "SELECT p.*, c.first_name, c.last_name, (SELECT status FROM {$wpdb->prefix}studiofy_invoices WHERE project_id = p.id LIMIT 1) as payment_status FROM {$wpdb->prefix}studiofy_projects p LEFT JOIN {$wpdb->prefix}studiofy_customers c ON p.customer_id = c.id ORDER BY p.$orderby $order";
        $items = $wpdb->get_results($sql);
        ?>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="studiofy_bulk_project">
            <?php wp_nonce_field('bulk_project', 'studiofy_nonce'); ?>
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <select name="bulk_action">
                        <option value="-1">Bulk Actions</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="submit" class="button action">Apply</button>
                </div>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><td id="cb" class="manage-column column-cb check-column"><input type="checkbox"></td><th class="manage-column sortable"><?php echo $this->sort_link('Project ID', 'id'); ?></th><th class="manage-column sortable"><?php echo $this->sort_link('Project Name', 'title'); ?></th><th class="manage-column">Customer Name</th><th class="manage-column sortable"><?php echo $this->sort_link('Status', 'status'); ?></th><th class="manage-column">Payment Status</th><th class="manage-column">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($items as $item): 
                        $edit_url = "?page=studiofy-projects&action=edit&id={$item->id}";
                        $del_url = wp_nonce_url(admin_url('admin-post.php?action=studiofy_delete_project&id='.$item->id), 'delete_project_'.$item->id);
                        $payment_label = $item->payment_status ? $item->payment_status : 'Unpaid';
                        $customer_name = $item->first_name ? esc_html($item->first_name . ' ' . $item->last_name) : 'Unknown';
                    ?>
                        <tr>
                            <th scope="row" class="check-column"><input type="checkbox" name="ids[]" value="<?php echo $item->id; ?>"></th>
                            <td><?php echo $item->id; ?></td>
                            <td><strong><a href="<?php echo $edit_url; ?>"><?php echo esc_html($item->title); ?></a></strong></td>
                            <td><?php echo $customer_name; ?></td>
                            <td><span class="studiofy-badge <?php echo esc_attr($item->status); ?>"><?php echo esc_html(str_replace('_',' ',$item->status)); ?></span></td>
                            <td><span class="studiofy-badge <?php echo strtolower($payment_label); ?>"><?php echo $payment_label; ?></span></td>
                            <td><a href="<?php echo $edit_url; ?>" class="button button-small">Edit</a> <a href="<?php echo $del_url; ?>" onclick="return confirm('Delete?')" class="button button-small" style="color:#b32d2e;">Delete</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
        <?php
    }

    private function get_projects_by_status(): array {
        global $wpdb;
        $table = $wpdb->prefix . 'studiofy_projects';
        $results = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
        $sorted = ['todo' => [], 'in_progress' => [], 'future' => []];
        foreach ($results as $row) { if (isset($sorted[$row->status])) $sorted[$row->status][] = $row; }
        return $sorted;
    }

    private function render_form(): void {
        global $wpdb;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($id) {
            $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_projects WHERE id = %d", $id));
        } else {
            $data = new \stdClass();
            $data->id = 0;
            $data->title = '';
            $data->customer_id = 0;
            $data->status = 'todo';
            $data->budget = '';
            $data->tax_status = 'taxed';
            $data->notes = '';
        }

        $customers = $wpdb->get_results("SELECT id, first_name, last_name FROM {$wpdb->prefix}studiofy_customers");
        $tax_status = $data->tax_status;
        ?>
        <div class="wrap">
            <h1><?php echo $id ? 'Edit Project' : 'New Project'; ?></h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="studiofy-card-form">
                <input type="hidden" name="action" value="studiofy_save_project">
                <?php wp_nonce_field('save_project', 'studiofy_nonce'); ?>
                <?php if($data->id) echo '<input type="hidden" name="id" value="'.$data->id.'">'; ?>
                <table class="form-table">
                    <tr><th scope="row"><label>Title *</label></th><td><input type="text" name="title" required value="<?php echo esc_attr($data->title); ?>" class="regular-text"></td></tr>
                    <tr><th scope="row"><label>Customer *</label></th><td><select name="customer_id" required><option value="">Select</option><?php foreach($customers as $c) echo "<option value='{$c->id}' ".selected($data->customer_id, $c->id, false).">{$c->first_name} {$c->last_name}</option>"; ?></select></td></tr>
                    <tr><th scope="row"><label>Status</label></th><td><select name="status"><option value="todo" <?php selected($data->status, 'todo'); ?>>To Do</option><option value="in_progress" <?php selected($data->status, 'in_progress'); ?>>In Progress</option><option value="future" <?php selected($data->status, 'future'); ?>>Future</option></select></td></tr>
                    <tr><th scope="row"><label>Budget</label></th><td><input type="text" name="budget" class="regular-text" placeholder="$0.00" value="<?php echo esc_attr($data->budget ? '$'.number_format((float)$data->budget, 2) : ''); ?>"></td></tr>
                    <tr><th scope="row">Tax</th><td><label><input type="radio" name="tax_status" value="taxed" <?php checked($tax_status, 'taxed'); ?>> Taxable</label> <label><input type="radio" name="tax_status" value="exempt" <?php checked($tax_status, 'exempt'); ?>> Exempt</label></td></tr>
                    <tr><th scope="row"><label>Notes</label></th><td><textarea name="notes" rows="5" class="large-text"><?php echo esc_textarea($data->notes); ?></textarea></td></tr>
                </table>
                <p class="submit"><button type="submit" class="button button-primary">Save Project</button> <a href="<?php echo admin_url('admin.php?page=studiofy-projects'); ?>" class="button">Cancel</a></p>
            </form>
        </div>
        <?php
    }

    public function handle_save(): void {
        if (!isset($_POST['studiofy_nonce']) || !wp_verify_nonce($_POST['studiofy_nonce'], 'save_project')) wp_die('Security check failed');
        global $wpdb;
        $budget = isset($_POST['budget']) ? preg_replace('/[^\d.]/', '', $_POST['budget']) : 0;
        $data = ['title' => sanitize_text_field($_POST['title']), 'customer_id' => (int)$_POST['customer_id'], 'status' => sanitize_text_field($_POST['status']), 'budget' => (float)$budget, 'tax_status' => sanitize_text_field($_POST['tax_status']), 'notes' => sanitize_textarea_field($_POST['notes'])];
        if(!empty($_POST['id'])) $wpdb->update($wpdb->prefix.'studiofy_projects', $data, ['id'=>(int)$_POST['id']]);
        else $wpdb->insert($wpdb->prefix.'studiofy_projects', array_merge($data, ['created_at' => current_time('mysql')]));
        wp_redirect(admin_url('admin.php?page=studiofy-projects')); exit;
    }

    public function handle_delete(): void { check_admin_referer('delete_project_'.$_GET['id']); global $wpdb; $wpdb->delete($wpdb->prefix.'studiofy_projects', ['id'=>(int)$_GET['id']]); wp_redirect(admin_url('admin.php?page=studiofy-projects')); exit; }
    public function handle_bulk(): void { check_admin_referer('bulk_project', 'studiofy_nonce'); if ($_POST['bulk_action'] === 'delete' && !empty($_POST['ids'])) { global $wpdb; $ids = array_map('intval', $_POST['ids']); $in = implode(',', $ids); $wpdb->query("DELETE FROM {$wpdb->prefix}studiofy_projects WHERE id IN ($in)"); } wp_redirect(admin_url('admin.php?page=studiofy-projects')); exit; }
}
