<?php
/**
 * Project Controller
 * @package Studiofy\Admin
 * @version 2.2.3
 */

declare(strict_types=1);

namespace Studiofy\Admin;

use function Studiofy\studiofy_get_asset_version;

class ProjectController {

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
            $this->render_kanban_board();
        }
    }

    public function render_kanban_board(): void {
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
                    <p>Create your first project to start tracking work.</p>
                    <a href="?page=studiofy-projects&action=new" class="button button-primary button-large">Create Project</a>
                </div>
            <?php else: 
                $this->render_kanban_html();
            endif; ?>
        </div>
        <?php
        require_once STUDIOFY_PATH . 'templates/admin/modal-project.php';
    }

    private function render_kanban_html(): void {
        $projects = $this->get_projects_by_status();
        ?>
        <div class="studiofy-kanban-board">
            <?php foreach(['todo', 'in_progress', 'future'] as $status): ?>
            <div class="studiofy-column" data-status="<?php echo $status; ?>">
                <h2 class="studiofy-col-title"><?php echo ucfirst(str_replace('_',' ',$status)); ?></h2>
                <div class="studiofy-card-container">
                    <?php foreach ($projects[$status] as $project): ?>
                        <div class="studiofy-card" data-id="<?php echo esc_attr($project->id); ?>">
                            <div class="studiofy-card-header"><strong><?php echo esc_html($project->title); ?></strong></div>
                            <div class="studiofy-card-body">
                                <p><?php echo esc_html($project->budget ? '$'.number_format($project->budget, 2) : ''); ?></p>
                            </div>
                            <div class="studiofy-card-actions">
                                <button class="button button-small" onclick="StudiofyKanban.editProject(<?php echo $project->id; ?>)">Manage</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function get_projects_by_status(): array {
        global $wpdb;
        $table = $wpdb->prefix . 'studiofy_projects';
        $results = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
        $sorted = ['todo' => [], 'in_progress' => [], 'future' => []];
        foreach ($results as $row) {
            if (isset($sorted[$row->status])) {
                $sorted[$row->status][] = $row;
            }
        }
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

                <table class="form-table">
                    <tr>
                        <th scope="row"><label>Project Title *</label></th>
                        <td><input type="text" name="title" required value="<?php echo esc_attr($data->title ?? ''); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Customer *</label></th>
                        <td>
                            <select name="customer_id" required>
                                <option value="">Select Customer</option>
                                <?php foreach($customers as $c) echo "<option value='{$c->id}' ".selected($data->customer_id ?? 0, $c->id, false).">{$c->first_name} {$c->last_name}</option>"; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Status</label></th>
                        <td>
                            <select name="status">
                                <option value="todo" <?php selected($data->status ?? '', 'todo'); ?>>To Do</option>
                                <option value="in_progress" <?php selected($data->status ?? '', 'in_progress'); ?>>In Progress</option>
                                <option value="future" <?php selected($data->status ?? '', 'future'); ?>>Future</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Budget</label></th>
                        <td>
                            <input type="text" name="budget" id="project_budget" class="regular-text" placeholder="$0.00" value="<?php echo esc_attr($data->budget ? '$'.number_format($data->budget, 2) : ''); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Tax Status</label></th>
                        <td>
                            <fieldset>
                                <label><input type="radio" name="tax_status" value="taxed" <?php checked($tax_status, 'taxed'); ?>> Taxable Project</label><br>
                                <label><input type="radio" name="tax_status" value="exempt" <?php checked($tax_status, 'exempt'); ?>> Tax Exempt</label>
                            </fieldset>
                            <p class="description">Invoices linked to this project will inherit this tax setting.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Notes</label></th>
                        <td><textarea name="notes" rows="5" class="large-text"><?php echo esc_textarea($data->notes ?? ''); ?></textarea></td>
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
        if (!isset($_POST['studiofy_nonce']) || !wp_verify_nonce($_POST['studiofy_nonce'], 'save_project')) {
            wp_die('Security check failed');
        }

        global $wpdb;
        
        // Strip non-numeric characters from budget
        $budget = isset($_POST['budget']) ? preg_replace('/[^\d.]/', '', $_POST['budget']) : 0;
        
        $data = [
            'title' => sanitize_text_field($_POST['title']),
            'customer_id' => (int)$_POST['customer_id'],
            'status' => sanitize_text_field($_POST['status']),
            'budget' => (float)$budget,
            'tax_status' => sanitize_text_field($_POST['tax_status']),
            'notes' => sanitize_textarea_field($_POST['notes']),
        ];
        
        if (!empty($_POST['id'])) {
            $wpdb->update($wpdb->prefix.'studiofy_projects', $data, ['id' => (int)$_POST['id']]);
        } else {
            $wpdb->insert($wpdb->prefix.'studiofy_projects', array_merge($data, ['created_at' => current_time('mysql')]));
        }
        
        wp_redirect(admin_url('admin.php?page=studiofy-projects'));
        exit;
    }

    public function handle_delete(): void {
        check_admin_referer('delete_project_'.$_GET['id']);
        global $wpdb;
        $wpdb->delete($wpdb->prefix.'studiofy_projects', ['id' => (int)$_GET['id']]);
        wp_redirect(admin_url('admin.php?page=studiofy-projects'));
        exit;
    }

    public function handle_bulk(): void {
        check_admin_referer('bulk_project', 'studiofy_nonce');
        if ($_POST['bulk_action'] === 'delete' && !empty($_POST['ids'])) {
            global $wpdb;
            $ids = array_map('intval', $_POST['ids']);
            $in = implode(',', $ids);
            $wpdb->query("DELETE FROM {$wpdb->prefix}studiofy_projects WHERE id IN ($in)");
        }
        wp_redirect(admin_url('admin.php?page=studiofy-projects'));
        exit;
    }
}
