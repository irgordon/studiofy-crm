<?php
/**
 * Customer Management
 * @package Studiofy\Admin
 * @version 2.1.0
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
        if (isset($_GET['msg'])) {
            $class = 'notice notice-success is-dismissible';
            $message = '';
            
            switch ($_GET['msg']) {
                case 'saved': $message = 'Customer created successfully.'; break;
                case 'deleted': $message = 'Customer deleted.'; break;
                case 'error': 
                    $class = 'notice notice-error is-dismissible';
                    $message = 'Error saving customer. Please check input.'; 
                    break;
            }
            if ($message) echo "<div class='$class'><p>$message</p></div>";
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
        if ($search) $where = $wpdb->prepare("WHERE first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR company LIKE %s", "%$search%", "%$search%", "%$search%", "%$search%");

        $items = $wpdb->get_results("SELECT *, (SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_projects WHERE customer_id = {$table}.id) as project_count, (SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_invoices WHERE customer_id = {$table}.id) as invoice_count FROM $table $where ORDER BY $orderby $order");

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Customers</h1>
            <button id="btn-new-customer" class="page-title-action button button-primary">New Customer</button>
            <hr class="wp-header-end">
            
            <form method="get">
                <input type="hidden" name="page" value="studiofy-customers" />
                <p class="search-box">
                    <label class="screen-reader-text" for="customer-search-input">Search:</label>
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
                                <a href="<?php echo $del_url; ?>" onclick="return confirm('Delete this customer?');" class="button button-small delete-link">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div id="modal-new-customer" class="studiofy-modal-overlay studiofy-hidden">
            <div class="studiofy-modal" role="dialog" aria-labelledby="modal-title">
                <div class="studiofy-modal-header">
                    <h2 id="modal-title" style="margin:0;">New Customer</h2>
                    <button type="button" class="close-modal" aria-label="Close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div class="studiofy-modal-body">
                    <form method="post" action="<?php echo admin_url('admin_post.php'); ?>" id="studiofy-customer-form">
                        <input type="hidden" name="action" value="studiofy_save_customer">
                        <?php wp_nonce_field('save_customer', 'studiofy_nonce'); ?>
                        
                        <div class="studiofy-form-row">
                            <div class="studiofy-col">
                                <label for="first_name">First Name <span class="required">*</span></label>
                                <input type="text" name="first_name" id="first_name" required class="widefat">
                            </div>
                            <div class="studiofy-col">
                                <label for="last_name">Last Name <span class="required">*</span></label>
                                <input type="text" name="last_name" id="last_name" required class="widefat">
                            </div>
                        </div>
                        
                        <div class="studiofy-form-row">
                            <div class="studiofy-col">
                                <label for="email">Email <span class="required">*</span></label>
                                <input type="email" name="email" id="email" required class="widefat" placeholder="client@example.com">
                            </div>
                            <div class="studiofy-col">
                                <label for="phone">Phone</label>
                                <input type="tel" name="phone" id="phone" class="widefat" placeholder="123-456-7890">
                            </div>
                        </div>
                        
                        <div class="studiofy-form-row">
                            <div class="studiofy-col">
                                <label for="company">Company</label>
                                <input type="text" name="company" id="company" class="widefat">
                            </div>
                            <div class="studiofy-col">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="widefat">
                                    <option value="Lead">Lead</option>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <hr>
                        <h4>Address</h4>
                        <div class="studiofy-form-row">
                            <div class="studiofy-col"><label>Street</label><input type="text" name="addr_street" class="widefat"></div>
                        </div>
                        <div class="studiofy-form-row">
                            <div class="studiofy-col"><label>City</label><input type="text" name="addr_city" class="widefat"></div>
                            <div class="studiofy-col"><label>State</label><input type="text" name="addr_state" class="widefat" maxlength="2"></div>
                            <div class="studiofy-col"><label>Zip</label><input type="text" name="addr_zip" class="widefat" pattern="[0-9]{5}"></div>
                        </div>
                        <hr>

                        <div class="studiofy-form-row">
                            <label for="notes">Notes</label>
                            <textarea name="notes" id="notes" rows="3" class="widefat"></textarea>
                        </div>
                        
                        <div class="studiofy-form-actions">
                            <button type="button" class="button close-modal">Cancel</button>
                            <button type="submit" class="button button-primary">Create Customer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    public function handle_save(): void {
        if (!isset($_POST['studiofy_nonce']) || !wp_verify_nonce($_POST['studiofy_nonce'], 'save_customer')) {
            wp_die('Security check failed. Please refresh and try again.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user.');
        }
        
        global $wpdb;
        
        $address = implode(', ', array_filter([
            sanitize_text_field($_POST['addr_street'] ?? ''),
            sanitize_text_field($_POST['addr_city'] ?? ''),
            sanitize_text_field($_POST['addr_state'] ?? ''),
            sanitize_text_field($_POST['addr_zip'] ?? '')
        ]));

        $result = $wpdb->insert($wpdb->prefix.'studiofy_customers', [
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => $this->encryption->encrypt(sanitize_text_field($_POST['phone'])),
            'company' => sanitize_text_field($_POST['company']),
            'address' => $this->encryption->encrypt($address),
            'notes' => sanitize_textarea_field($_POST['notes']),
            'status' => sanitize_text_field($_POST['status'])
        ]);

        if ($result === false) {
            wp_die('Database Error: ' . $wpdb->last_error);
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
