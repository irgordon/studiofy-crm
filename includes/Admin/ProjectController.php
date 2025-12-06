<?php
/**
 * Project Controller
 * @package Studiofy\Admin
 * @version 2.2.26
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
            'nonce' => wp_create_nonce('wp_rest')
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
                    <p>Create your first project to start tracking work, deadlines, and budgets.</p>
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

        // Define columns with friendly names and color classes
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
                        // Count Tasks for this Project
                        $task_count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_tasks t 
                             JOIN {$wpdb->prefix}studiofy_milestones m ON t.milestone_id = m.id 
                             WHERE m.project_id = %d", 
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
                                <div class="meta-item">
                                    <span class="dashicons dashicons-list-view"></span>
                                    <?php echo (int)$task_count . ' Tasks'; ?>
                                </div>
                            </div>
                            <div class="studiofy-card-actions">
                                <button class="button button-small" onclick="StudiofyModal.open(<?php echo $project->id; ?>)">View Tasks</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
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
                    <label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label>
                    <select name="bulk_action" id="bulk-action-selector-top">
                        <option value="-1">Bulk Actions</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="submit" class="button action">Apply</button>
                </div>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox"></td>
                        <th class="manage-column sortable"><?php echo $this->sort_link('Project ID', 'id'); ?></th>
                        <th class="manage-column sortable"><?php echo $this->sort_link('Project Name', 'title'); ?></th>
                        <th class="manage-column">Customer Name</th>
                        <th class="manage-column sortable"><?php echo $this->sort_link('Status', 'status'); ?></th>
                        <th class="manage-column">Payment Status</th>
                        <th class="manage-column">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): 
                        $edit_url = "?page=studiofy-projects&action=edit&id={$item->id}";
                        $del_url = wp_nonce_url(admin_url('admin-post.php?action=studiofy_delete_project&id='.$item->id), 'delete_project_'.$item->id);
                        $payment_label = $item->payment_status ? $item->payment_status : 'Unpaid';
                        $customer_name = $item->first_name ? esc_html($item->first_name . ' ' . $item->last_name) : 'Unknown';
                    ?>
                        <tr>
                            <th scope="row" class="check-column"><label class="screen-reader-text" for="cb-select-<?php echo $item->id; ?>">Select <?php echo esc_html($item->title); ?></label><input id="cb-select-<?php echo $item->id; ?>" type="checkbox" name="ids[]" value="<?php echo $item->id; ?>"></th>
                            <td><?php echo $item->id; ?></td>
                            <td><strong><a href="<?php echo $edit_url; ?>"><?php echo esc_html($item->title); ?></a></strong></td>
                            <td><?php echo $customer_name; ?></td>
                            <td><span class="studiofy-badge <?php echo esc_attr($item->status); ?>"><?php echo esc_html(str_replace('_',' ',$item->status)); ?></span></td>
                            <td><span class="studiofy-badge <?php echo strtolower($payment_label); ?>"><?php echo $payment_label; ?></span></td>
                            <td><a href="<?php echo $edit_url; ?>" class="button button-small">Edit</a> <a href="<?php echo $del_url; ?>" onclick="return confirm('Delete this project?')" class="button button-small" style="color:#b32d2e;">Delete</a></td>
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
        $data = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_projects WHERE id = %d", $id)) : null;
        $customers = $wpdb->get_results("SELECT id, first_name, last_name FROM {$wpdb->prefix}studiofy_customers");
        $tax_status = $data ? $data->tax_status : 'taxed';

        ?>
        <div class="wrap">
            <h1><?php echo $data ? 'Edit Project' : 'New Project'; ?></h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="studiofy-card-form">
                <input type="hidden" name="action" value="studiofy_save_project">
                <?php wp_nonce_field('save_project', 'studiofy_nonce'); ?>
                <?php if($data) echo '<input type="hidden" name="id" value="'.$data->id.'">'; ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="project_title">Project Title *</label></th>
                        <td><input type="text" name="title" id="project_title" required value="<?php echo esc_attr($data->title ?? ''); ?>" class="regular-text" title="Project Title"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="project_customer">Customer *</label></th>
                        <td>
                            <select name="customer_id" id="project_customer" required title="Select Customer">
                                <option value="">Select Customer</option>
                                <?php foreach($customers as $c) echo "<option value='{$c->id}' ".selected($data->customer_id ?? 0, $c->id, false).">{$c->first_name} {$c->last_name}</option>"; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="project_status">Status</label></th>
                        <td>
                            <select name="status" id="project_status" title="Project Status">
                                <option value="todo" <?php selected($data->status ?? '', 'todo'); ?>>To Do</option>
                                <option value="in_progress" <?php selected($data->status ?? '', 'in_progress'); ?>>In Progress</option>
                                <option value="future" <?php selected($data->status ?? '', 'future'); ?>>Future</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="project_budget">Budget</label></th>
                        <td>
                            <input type="text" name="budget" id="project_budget" class="regular-text" placeholder="$0.00" value="<?php echo esc_attr($data->budget ? '$'.number_format((float)$data->budget, 2) : ''); ?>" title="Budget Amount">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Tax Status</th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><span>Tax Status</span></legend>
                                <label for="tax_taxed"><input type="radio" name="tax_status" id="tax_taxed" value="taxed" <?php checked($tax_status, 'taxed'); ?>> Taxable Project</label><br>
                                <label for="tax_exempt"><input type="radio" name="tax_status" id="tax_exempt" value="exempt" <?php checked($tax_status, 'exempt'); ?>> Tax Exempt</label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="project_notes">Notes</label></th>
                        <td><textarea name="notes" id="project_notes" rows="5" class="large-text" title="Project Notes"><?php echo esc_textarea($data->notes ?? ''); ?></textarea></td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">Save Project</button>
                    <a href="<?php echo admin_url('admin.php?page=studiofy-projects'); ?>" class="button">Cancel</a>
                </p>
            </form>
        </div>
        <?php
    }

    public function handle_save(): void {
        if (!isset($_POST['studiofy_nonce']) || !wp_verify_nonce($_POST['studiofy_nonce'], 'save_project')) wp_die('Security check failed');
        global $wpdb;
        $budget = isset($_POST['budget']) ? preg_replace('/[^\d.]/', '', $_POST['budget']) : 0;
        $data = [
            'title' => sanitize_text_field($_POST['title']),
            'customer_id' => (int)$_POST['customer_id'],
            'status' => sanitize_text_field($_POST['status']),
            'budget' => (float)$budget,
            'tax_status' => sanitize_text_field($_POST['tax_status']),
            'notes' => sanitize_textarea_field($_POST['notes']),
        ];
        if(!empty($_POST['id'])) $wpdb->update($wpdb->prefix.'studiofy_projects', $data, ['id'=>(int)$_POST['id']]);
        else $wpdb->insert($wpdb->prefix.'studiofy_projects', $data);
        wp_redirect(admin_url('admin.php?page=studiofy-projects')); exit;
    }

    public function handle_delete(): void {
        check_admin_referer('delete_project_'.$_GET['id']);
        global $wpdb;
        $wpdb->delete($wpdb->prefix.'studiofy_projects', ['id'=>(int)$_GET['id']]);
        wp_redirect(admin_url('admin.php?page=studiofy-projects')); exit;
    }

    public function handle_bulk(): void {
        check_admin_referer('bulk_project', 'studiofy_nonce');
        if ($_POST['bulk_action'] === 'delete' && !empty($_POST['ids'])) {
            global $wpdb;
            $ids = array_map('intval', $_POST['ids']);
            $in = implode(',', $ids);
            $wpdb->query("DELETE FROM {$wpdb->prefix}studiofy_projects WHERE id IN ($in)");
        }
        wp_redirect(admin_url('admin.php?page=studiofy-projects')); exit;
    }
}
