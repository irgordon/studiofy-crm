<?php
/**
 * Customer Management
 * @package Studiofy\Admin
 * @version 2.0.5
 */

declare(strict_types=1);

namespace Studiofy\Admin;

use Studiofy\Utils\TableHelper;
use Studiofy\Security\Encryption;

class CustomerController {
    use TableHelper;

    private Encryption $encryption;

    public function __construct() {
        $this->encryption = new Encryption();
    }

    public function init(): void {
        add_action('admin_post_studiofy_save_customer', [$this, 'handle_save']);
        add_action('admin_post_studiofy_delete_customer', [$this, 'handle_delete']);
        add_action('admin_notices', [$this, 'display_notices']);
    }

    public function display_notices(): void {
        if (isset($_GET['msg']) && $_GET['msg'] === 'saved') {
            echo '<div class="notice notice-success is-dismissible"><p>Customer saved successfully.</p></div>';
        }
        if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
            echo '<div class="notice notice-success is-dismissible"><p>Customer deleted.</p></div>';
        }
    }

    public function render_page(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'studiofy_customers';
        
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $orderby = $_GET['orderby'] ?? 'created_at';
        $order = strtoupper($_GET['order'] ?? 'DESC');
        
        $allowed_sort = ['first_name', 'last_name', 'email', 'status', 'created_at'];
        if (!in_array($orderby, $allowed_sort)) $orderby = 'created_at';
        if (!in_array($order, ['ASC', 'DESC'])) $order = 'DESC';

        $where = '';
        if ($search) {
            $where = $wpdb->prepare("WHERE first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR company LIKE %s", "%$search%", "%$search%", "%$search%", "%$search%");
        }

        $items = $wpdb->get_results("SELECT *, (SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_projects WHERE customer_id = {$table}.id) as project_count, (SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_invoices WHERE customer_id = {$table}.id) as invoice_count FROM $table $where ORDER BY $orderby $order");

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Customers</h1>
            <button id="btn-new-customer" class="page-title-action">New Customer</button>
            <hr class="wp-header-end">
            
            <form method="get">
                <input type="hidden" name="page" value="studiofy-customers" />
                <p class="search-box">
                    <label class="screen-reader-text" for="customer-search-input">Search Customers:</label>
                    <input type="search" id="customer-search-input" name="s" value="<?php echo esc_attr($search); ?>">
                    <input type="submit" id="search-submit" class="button" value="Search Customers">
                </p>
            </form>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="manage-column sortable <?php echo strtolower($order); ?>"><?php echo $this->sort_link('Name', 'last_name'); ?></th>
                        <th class="manage-column">Contact</th>
                        <th class="manage-column">Company</th>
                        <th class="manage-column sortable <?php echo strtolower($order); ?>"><?php echo $this->sort_link('Status', 'status'); ?></th>
                        <th class="manage-column">Projects</th>
                        <th class="manage-column">Invoices</th>
                        <th class="manage-column">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="7">No customers found. Click "New Customer" to add one.</td></tr>
                    <?php else: foreach ($items as $item): 
                        $del_url = wp_nonce_url(admin_url('admin_post.php?action=studiofy_delete_customer&id=' . $item->id), 'delete_customer_' . $item->id);
                        $phone_decrypted = $this->encryption->decrypt($item->phone);
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html($item->first_name . ' ' . $item->last_name); ?></strong></td>
                            <td>
                                <div><span class="dashicons dashicons-email-alt"></span> <a href="mailto:<?php echo esc_attr($item->email); ?>"><?php echo esc_html($item->email); ?></a></div>
                                <div><span class="dashicons dashicons-phone"></span> <?php echo esc_html($phone_decrypted); ?></div>
                            </td>
                            <td><?php echo esc_html($item->company); ?></td>
                            <td><span class="studiofy-badge <?php echo esc_attr(strtolower($item->status)); ?>"><?php echo esc_html(ucfirst($item->status)); ?></span></td>
                            <td><?php echo esc_html($item->project_count); ?></td>
                            <td><?php echo esc_html($item->invoice_count); ?></td>
                            <td>
                                <a href="#" class="button button-small">Edit</a>
                                <a href="<?php echo $del_url; ?>" onclick="return confirm('Delete this customer?');" class="button button-small delete-link">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div id="modal-new-customer" class="studiofy-modal-overlay studiofy-hidden">
            <div class="studiofy-modal">
                <div class="studiofy-modal-header">
                    <h2>New Customer</h2>
                    <button type="button" class="close-modal">&times;</button>
                </div>
                <div class="studiofy-modal-body">
                    <form method="post" action="<?php echo admin_url('admin_post.php'); ?>">
                        <input type="hidden" name="action" value="studiofy_save_customer">
                        <?php wp_nonce_field('save_customer', 'studiofy_nonce'); ?>
                        
                        <div class="studiofy-form-row">
                            <div class="studiofy-col"><label>First Name *</label><input type="text" name="first_name" required class="widefat"></div>
                            <div class="studiofy-col"><label>Last Name *</label><input type="text" name="last_name" required class="widefat"></div>
                        </div>
                        <div class="studiofy-form-row">
                            <div class="studiofy-col"><label>Email *</label><input type="email" name="email" required class="widefat"></div>
                            <div class="studiofy-col"><label>Phone</label><input type="text" name="phone" class="widefat"></div>
                        </div>
                        <div class="studiofy-form-row">
                            <div class="studiofy-col"><label>Company</label><input type="text" name="company" class="widefat"></div>
                            <div class="studiofy-col"><label>Status</label>
                                <select name="status" class="widefat">
                                    <option value="Lead">Lead</option>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="studiofy-form-row"><label>Address</label><input type="text" name="address" class="widefat"></div>
                        <div class="studiofy-form-row"><label>Notes</label><textarea name="notes" rows="3" class="widefat"></textarea></div>
                        
                        <div class="studiofy-form-actions">
                            <button type="button" class="button close-modal">Cancel</button>
                            <button type="submit" class="button button-primary">Create Customer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($){
                $('#btn-new-customer').click(function(e){ e.preventDefault(); $('#modal-new-customer').removeClass('studiofy-hidden'); });
                $('.close-modal').click(function(e){ e.preventDefault(); $('#modal-new-customer').addClass('studiofy-hidden'); });
            });
        </script>
        <?php
    }

    public function handle_save(): void {
        check_admin_referer('save_customer', 'studiofy_nonce');
        
        global $wpdb;
        
        $result = $wpdb->insert($wpdb->prefix.'studiofy_customers', [
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => $this->encryption->encrypt(sanitize_text_field($_POST['phone'])),
            'company' => sanitize_text_field($_POST['company']),
            'address' => $this->encryption->encrypt(sanitize_text_field($_POST['address'])),
            'notes' => sanitize_textarea_field($_POST['notes']),
            'status' => sanitize_text_field($_POST['status'])
        ]);

        if ($result === false) {
            wp_die('Error saving customer: ' . $wpdb->last_error);
        }
        
        wp_redirect(admin_url('admin.php?page=studiofy-customers&msg=saved'));
        exit;
    }

    public function handle_delete(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        check_admin_referer('delete_customer_' . $_GET['id']);
        
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'studiofy_customers', ['id' => (int) $_GET['id']]);
        
        wp_redirect(admin_url('admin.php?page=studiofy-customers&msg=deleted'));
        exit;
    }
}
