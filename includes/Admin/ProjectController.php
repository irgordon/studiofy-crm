<?php
/**
 * Project Controller
 * @package Studiofy\Admin
 * @version 2.1.5
 */

declare(strict_types=1);

namespace Studiofy\Admin;

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
            $this->render_list();
        }
    }

    private function render_list(): void {
        global $wpdb;
        
        $orderby = $_GET['orderby'] ?? 'id';
        $order = strtoupper($_GET['order'] ?? 'DESC');
        
        // Complex query to determine billing status
        $sql = "SELECT p.*, 
                (SELECT status FROM {$wpdb->prefix}studiofy_invoices WHERE project_id = p.id LIMIT 1) as inv_status
                FROM {$wpdb->prefix}studiofy_projects p 
                ORDER BY $orderby $order";
                
        $items = $wpdb->get_results($sql);

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Projects</h1>
            <a href="?page=studiofy-projects&action=new" class="page-title-action">Add New</a>
            <hr class="wp-header-end">

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
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column"><input type="checkbox"></td>
                            <th class="manage-column sortable"><?php echo $this->sort_link('ID', 'id'); ?></th>
                            <th class="manage-column sortable"><?php echo $this->sort_link('Project Name', 'title'); ?></th>
                            <th class="manage-column sortable"><?php echo $this->sort_link('Status', 'status'); ?></th>
                            <th class="manage-column">Billing Status</th>
                            <th class="manage-column">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?><tr><td colspan="6">No projects found.</td></tr><?php else: foreach ($items as $item): 
                            $edit_url = "?page=studiofy-projects&action=edit&id={$item->id}";
                            $del_url = wp_nonce_url(admin_url('admin-post.php?action=studiofy_delete_project&id='.$item->id), 'delete_project_'.$item->id);
                            
                            // Billing Status Logic
                            $billing_status = 'Unbilled';
                            if ($item->inv_status) {
                                $billing_status = ucfirst($item->inv_status); // e.g. Paid, Sent, Draft
                            }
                        ?>
                            <tr>
                                <th scope="row" class="check-column"><input type="checkbox" name="ids[]" value="<?php echo $item->id; ?>"></th>
                                <td><?php echo $item->id; ?></td>
                                <td class="has-row-actions column-primary">
                                    <strong><a href="<?php echo $edit_url; ?>"><?php echo esc_html($item->title); ?></a></strong>
                                    <div class="row-actions">
                                        <span class="edit"><a href="<?php echo $edit_url; ?>">Edit</a> | </span>
                                        <span class="trash"><a href="<?php echo $del_url; ?>" onclick="return confirm('Delete?')" class="submitdelete">Delete</a></span>
                                    </div>
                                </td>
                                <td><span class="studiofy-badge <?php echo esc_attr($item->status); ?>"><?php echo esc_html(str_replace('_',' ',$item->status)); ?></span></td>
                                <td><?php echo $billing_status; ?></td>
                                <td><a href="<?php echo $edit_url; ?>" class="button button-small">Manage</a></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </form>
        </div>
        <?php
    }

    private function render_form(): void {
        global $wpdb;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $data = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_projects WHERE id = %d", $id)) : null;
        $customers = $wpdb->get_results("SELECT id, first_name, last_name FROM {$wpdb->prefix}studiofy_customers");

        ?>
        <div class="wrap">
            <h1><?php echo $data ? 'Edit Project' : 'New Project'; ?></h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="studiofy-card-form">
                <input type="hidden" name="action" value="studiofy_save_project">
                <?php wp_nonce_field('save_project', 'studiofy_nonce'); ?>
                <?php if($data) echo '<input type="hidden" name="id" value="'.$data->id.'">'; ?>

                <table class="form-table">
                    <tr><th>Title</th><td><input type="text" name="title" required value="<?php echo esc_attr($data->title ?? ''); ?>" class="regular-text"></td></tr>
                    <tr><th>Customer</th><td><select name="customer_id" required>
                        <?php foreach($customers as $c) echo "<option value='{$c->id}' ".selected($data->customer_id ?? 0, $c->id, false).">{$c->first_name} {$c->last_name}</option>"; ?>
                    </select></td></tr>
                    <tr><th>Status</th><td><select name="status">
                        <option value="todo" <?php selected($data->status ?? '', 'todo'); ?>>To Do</option>
                        <option value="in_progress" <?php selected($data->status ?? '', 'in_progress'); ?>>In Progress</option>
                        <option value="future" <?php selected($data->status ?? '', 'future'); ?>>Future</option>
                    </select></td></tr>
                    <tr><th>Budget</th><td><input type="number" step="0.01" name="budget" value="<?php echo esc_attr($data->budget ?? ''); ?>"></td></tr>
                    <tr><th>Notes</th><td><textarea name="notes" rows="5" class="large-text"><?php echo esc_textarea($data->notes ?? ''); ?></textarea></td></tr>
                </table>
                <p class="submit"><button type="submit" class="button button-primary">Save Project</button></p>
            </form>
        </div>
        <?php
    }

    public function handle_save(): void {
        check_admin_referer('save_project', 'studiofy_nonce');
        global $wpdb;
        $data = [
            'title' => sanitize_text_field($_POST['title']),
            'customer_id' => (int)$_POST['customer_id'],
            'status' => sanitize_text_field($_POST['status']),
            'budget' => (float)$_POST['budget'],
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
