<?php
/**
 * Customer Management
 * @package Studiofy\Admin
 * @version 2.1.1
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
                    $message = 'Error saving customer. Please check required fields.'; 
                    break;
                case 'nonce':
                    $class = 'notice notice-error is-dismissible';
                    $message = 'Security check failed. Please try again.';
                    break;
            }
            
            if ($message) {
                echo "<div class='$class'><p>$message</p></div>";
            }
        }
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
            <a href="<?php echo admin_url('admin.php?page=studiofy-customers&action=new'); ?>" class="page-title-action">Add New</a>
            <hr class="wp-header-end">
            
            <form method="get">
                <input type="hidden" name="page" value="studiofy-customers" />
                <p class="search-box">
                    <label class="screen-reader-text" for="customer-search-input">Search Customers:</label>
                    <input type="search" id="customer-search-input" name="s" value="<?php echo esc_attr($search); ?>">
                    <input type="submit" id="search-submit" class="button" value="Search Customers">
                </p>
            </form>

            <table class="wp-list-table widefat fixed striped table-view-list">
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
                        <tr><td colspan="7">No customers found. Click "Add New" to create one.</td></tr>
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
                                <a href="<?php echo $del_url; ?>" onclick="return confirm('Delete this customer?');" class="button button-small delete-link" style="color:#b32d2e;">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Renders the Full Page Form (Replaces Modal)
     */
    private function render_form(): void {
        ?>
        <div class="wrap">
            <h1>Add New Customer</h1>
            
            <form method="post" action="<?php echo admin_url('admin_post.php'); ?>" class="studiofy-card-form">
                <input type="hidden" name="action" value="studiofy_save_customer">
                <?php wp_nonce_field('save_customer', 'studiofy_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="first_name">First Name <span class="required">*</span></label></th>
                        <td><input type="text" name="first_name" id="first_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="last_name">Last Name <span class="required">*</span></label></th>
                        <td><input type="text" name="last_name" id="last_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="email">Email <span class="required">*</span></label></th>
                        <td>
                            <input type="email" name="email" id="email" class="regular-text" required>
                            <p class="description studiofy-error-msg" id="email-error"></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="phone">Phone</label></th>
                        <td><input type="tel" name="phone" id="phone" class="regular-text" placeholder="(555) 555-5555"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="company">Company</label></th>
                        <td><input type="text" name="company" id="company" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="status">Status</label></th>
                        <td>
                            <select name="status" id="status">
                                <option value="Lead">Lead</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <hr>
                <h3>Address Information</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label>Street Address</label></th>
                        <td><input type="text" name="addr_street" class="large-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label>City / State / Zip</label></th>
                        <td>
                            <input type="text" name="addr_city" placeholder="City" style="width: 150px;">
                            <input type="text" name="addr_state" placeholder="State" maxlength="2" style="width: 60px;">
                            <input type="text" name="addr_zip" placeholder="Zip" maxlength="5" style="width: 80px;">
                        </td>
                    </tr>
                </table>

                <hr>
                <h3>Notes</h3>
                <textarea name="notes" rows="5" class="large-text code"></textarea>

                <p class="submit">
                    <button type="submit" class="button button-primary">Save Customer</button>
                    <a href="<?php echo admin_url('admin.php?page=studiofy-customers'); ?>" class="button button-secondary">Cancel</a>
                </p>
            </form>
        </div>
        <?php
    }

    public function handle_save(): void {
        if (!isset($_POST['studiofy_nonce']) || !wp_verify_nonce($_POST['studiofy_nonce'], 'save_customer')) {
            wp_redirect(admin_url('admin.php?page=studiofy-customers&msg=nonce'));
            exit;
        }

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // Basic Validation
        if (empty($_POST['first_name']) || empty($_POST['email'])) {
            wp_redirect(admin_url('admin.php?page=studiofy-customers&action=new&msg=error'));
            exit;
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
