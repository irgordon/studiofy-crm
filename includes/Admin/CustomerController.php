<?php
/**
 * Customer Controller
 * @package Studiofy\Admin
 * @version 2.2.36
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
        add_action('admin_post_studiofy_bulk_customer', [$this, 'handle_bulk']);
        add_action('admin_notices', [$this, 'display_notices']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_maps']);
    }

    // ... (enqueue_maps, display_notices, render_page, render_list, render_form same as previous) ...
    public function enqueue_maps(): void { if (!isset($_GET['page']) || $_GET['page'] !== 'studiofy-customers') return; $options = get_option('studiofy_branding'); $key = $options['google_maps_key'] ?? ''; if (!empty($key)) { wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($key) . '&libraries=places', [], null, true); } }
    public function display_notices(): void { if (isset($_GET['msg'])) { $msg = ''; switch ($_GET['msg']) { case 'saved': $msg = 'Customer saved successfully.'; break; case 'deleted': $msg = 'Customer(s) deleted.'; break; case 'nonce': $msg = 'Security check failed.'; break; case 'error': $msg = 'Error saving customer. Check required fields.'; break; } if ($msg) echo "<div class='notice notice-success is-dismissible'><p>$msg</p></div>"; } }
    public function render_page(): void { $action = $_GET['action'] ?? 'list'; if ($action === 'new' || $action === 'edit' || $action === 'clone') { $this->render_form(); } else { $this->render_list(); } }
    private function render_list(): void { global $wpdb; $table = $wpdb->prefix . 'studiofy_customers'; $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : ''; $orderby = $_GET['orderby'] ?? 'created_at'; $order = strtoupper($_GET['order'] ?? 'DESC'); $where = ''; if ($search) { $where = $wpdb->prepare("WHERE first_name LIKE %s OR last_name LIKE %s OR email LIKE %s", "%$search%", "%$search%", "%$search%"); } $items = $wpdb->get_results("SELECT *, (SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_projects WHERE customer_id = {$table}.id) as project_count, (SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_invoices WHERE customer_id = {$table}.id) as invoice_count FROM $table $where ORDER BY $orderby $order"); ?> <div class="wrap"> <h1 class="wp-heading-inline">Customers</h1> <a href="?page=studiofy-customers&action=new" class="page-title-action">Add New</a> <hr class="wp-header-end"> <form method="post" action="<?php echo admin_url('admin-post.php'); ?>"> <input type="hidden" name="action" value="studiofy_bulk_customer"> <?php wp_nonce_field('bulk_customer', 'studiofy_nonce'); ?> <div class="tablenav top"> <div class="alignleft actions bulkactions"> <select name="bulk_action"> <option value="-1">Bulk Actions</option> <option value="delete">Delete</option> </select> <button type="submit" class="button action">Apply</button> </div> <div class="alignright"> <input type="search" name="s" value="<?php echo esc_attr($search); ?>"> <button type="button" class="button" onclick="window.location.href='?page=studiofy-customers&s='+this.previousElementSibling.value">Search</button> </div> </div> <table class="wp-list-table widefat fixed striped"> <thead> <tr> <td id="cb" class="manage-column column-cb check-column"><input type="checkbox"></td> <th class="manage-column sortable"><?php echo $this->sort_link('Name', 'last_name'); ?></th> <th class="manage-column">Contact</th> <th class="manage-column">Company</th> <th class="manage-column sortable"><?php echo $this->sort_link('Status', 'status'); ?></th> <th class="manage-column">Stats</th> </tr> </thead> <tbody> <?php if (empty($items)): ?> <tr><td colspan="6">No customers found.</td></tr> <?php else: foreach ($items as $item): $edit_url = "?page=studiofy-customers&action=edit&id={$item->id}"; $clone_url = "?page=studiofy-customers&action=clone&id={$item->id}"; $del_url = wp_nonce_url(admin_url('admin-post.php?action=studiofy_delete_customer&id=' . $item->id), 'delete_customer_' . $item->id); $phone = $this->encryption->decrypt($item->phone); ?> <tr> <th scope="row" class="check-column"><input type="checkbox" name="ids[]" value="<?php echo $item->id; ?>"></th> <td class="has-row-actions column-primary"> <strong><a href="<?php echo $edit_url; ?>"><?php echo esc_html($item->first_name . ' ' . $item->last_name); ?></a></strong> <div class="row-actions"> <span class="edit"><a href="<?php echo $edit_url; ?>">Edit</a> | </span> <span class="clone"><a href="<?php echo $clone_url; ?>">Clone</a> | </span> <span class="trash"><a href="<?php echo $del_url; ?>" onclick="return confirm('Delete?')" class="submitdelete">Delete</a></span> </div> </td> <td> <a href="mailto:<?php echo esc_attr($item->email); ?>"><?php echo esc_html($item->email); ?></a><br> <?php echo esc_html($phone); ?> </td> <td><?php echo esc_html($item->company); ?></td> <td><span class="studiofy-badge <?php echo esc_attr(strtolower($item->status)); ?>"><?php echo esc_html($item->status); ?></span></td> <td>Projects: <?php echo $item->project_count; ?> | Invoices: <?php echo $item->invoice_count; ?></td> </tr> <?php endforeach; endif; ?> </tbody> </table> </form> </div> <?php }
    private function render_form(): void { global $wpdb; $data = null; $id = isset($_GET['id']) ? (int)$_GET['id'] : 0; $is_clone = isset($_GET['action']) && $_GET['action'] === 'clone'; if ($id) { $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_customers WHERE id = %d", $id)); if ($data) { $data->phone = $this->encryption->decrypt($data->phone); $decrypted_addr = $this->encryption->decrypt($data->address); } } $addr_parts = isset($decrypted_addr) ? array_map('trim', explode(',', $decrypted_addr)) : []; $street = $addr_parts[0] ?? ''; $city   = $addr_parts[1] ?? ''; $state  = $addr_parts[2] ?? ''; $zip    = $addr_parts[3] ?? ''; $title = $is_clone ? "Clone Customer" : ($data ? "Edit Customer" : "Add New Customer"); $btn_text = $is_clone ? "Create Clone" : ($data ? "Update Customer" : "Create Customer"); if ($is_clone) { $data->id = '';  $data->first_name .= ' (Clone)'; } ?> <div class="wrap"> <h1><?php echo $title; ?></h1> <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="studiofy-card-form" id="studiofy-customer-form"> <input type="hidden" name="action" value="studiofy_save_customer"> <?php wp_nonce_field('save_customer', 'studiofy_nonce'); ?> <?php if ($data && !$is_clone) echo '<input type="hidden" name="id" value="' . $data->id . '">'; ?> <table class="form-table"> <tr><th scope="row"><label for="first_name">First Name *</label></th><td><input type="text" name="first_name" id="first_name" class="regular-text" required value="<?php echo esc_attr($data->first_name ?? ''); ?>"></td></tr> <tr><th scope="row"><label for="last_name">Last Name *</label></th><td><input type="text" name="last_name" id="last_name" class="regular-text" required value="<?php echo esc_attr($data->last_name ?? ''); ?>"></td></tr> <tr><th scope="row"><label for="email">Email *</label></th><td><input type="email" name="email" id="email" class="regular-text" required value="<?php echo esc_attr($data->email ?? ''); ?>"></td></tr> <tr><th scope="row"><label for="phone">Phone</label></th><td><input type="tel" name="phone" id="phone" class="regular-text" value="<?php echo esc_attr($data->phone ?? ''); ?>"></td></tr> <tr><th scope="row"><label for="company">Company</label></th><td><input type="text" name="company" id="company" class="regular-text" value="<?php echo esc_attr($data->company ?? ''); ?>"></td></tr> <tr><th scope="row"><label for="status">Status</label></th><td><select name="status" id="status"> <option <?php selected($data->status ?? '', 'Lead'); ?>>Lead</option> <option <?php selected($data->status ?? '', 'Active'); ?>>Active</option> <option <?php selected($data->status ?? '', 'Inactive'); ?>>Inactive</option> </select></td></tr> </table> <hr> <h3>Address</h3> <table class="form-table"> <tr><th scope="row"><label for="addr_street">Street</label></th><td><input type="text" name="addr_street" id="addr_street" class="large-text" value="<?php echo esc_attr($street); ?>" placeholder="Start typing to search..."></td></tr> <tr><th scope="row"><label for="addr_city">City/State/Zip</label></th><td> <input type="text" name="addr_city" id="addr_city" placeholder="City" value="<?php echo esc_attr($city); ?>"> <input type="text" name="addr_state" id="addr_state" placeholder="State" maxlength="2" size="2" value="<?php echo esc_attr($state); ?>"> <input type="text" name="addr_zip" id="addr_zip" placeholder="Zip" maxlength="10" size="10" value="<?php echo esc_attr($zip); ?>"> </td></tr> </table> <hr> <p class="submit"><button type="submit" class="button button-primary"><?php echo $btn_text; ?></button></p> </form> </div> <?php }

    public function handle_save(): void {
        check_admin_referer('save_customer', 'studiofy_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        
        global $wpdb;
        
        // FIX: Strict string casting
        $street = isset($_POST['addr_street']) ? (string)$_POST['addr_street'] : '';
        $city   = isset($_POST['addr_city']) ? (string)$_POST['addr_city'] : '';
        $state  = isset($_POST['addr_state']) ? (string)$_POST['addr_state'] : '';
        $zip    = isset($_POST['addr_zip']) ? (string)$_POST['addr_zip'] : '';

        $address = implode(', ', array_filter([$street, $city, $state, $zip]));

        $data = [
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => $this->encryption->encrypt(sanitize_text_field($_POST['phone'])),
            'company' => sanitize_text_field($_POST['company']),
            'address' => $this->encryption->encrypt($address),
            'status' => sanitize_text_field($_POST['status'])
        ];

        if (!empty($_POST['id'])) {
            $wpdb->update($wpdb->prefix.'studiofy_customers', $data, ['id' => (int)$_POST['id']]);
        } else {
            $wpdb->insert($wpdb->prefix.'studiofy_customers', array_merge($data, ['created_at' => current_time('mysql')]));
        }
        
        wp_redirect(admin_url('admin.php?page=studiofy-customers&msg=saved')); exit;
    }

    public function handle_bulk(): void {
        check_admin_referer('bulk_customer', 'studiofy_nonce');
        if ($_POST['bulk_action'] === 'delete' && !empty($_POST['ids'])) {
            global $wpdb;
            $ids = array_map('intval', $_POST['ids']);
            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}studiofy_customers WHERE id IN ($placeholders)", $ids));
            wp_redirect(admin_url('admin.php?page=studiofy-customers&msg=deleted')); exit;
        }
        wp_redirect(admin_url('admin.php?page=studiofy-customers')); exit;
    }

    public function handle_delete(): void {
        check_admin_referer('delete_customer_'.$_GET['id']);
        global $wpdb;
        $wpdb->delete($wpdb->prefix.'studiofy_customers', ['id' => (int)$_GET['id']]);
        wp_redirect(admin_url('admin.php?page=studiofy-customers&msg=deleted')); exit;
    }
}
