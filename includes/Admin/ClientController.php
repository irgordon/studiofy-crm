<?php
/**
 * Client Management
 * @package Studiofy\Admin
 * @version 2.0.4
 */

declare(strict_types=1);

namespace Studiofy\Admin;

use Studiofy\Utils\TableHelper;
use Studiofy\Security\Encryption;

class ClientController {
    use TableHelper;

    private Encryption $encryption;

    public function __construct() {
        $this->encryption = new Encryption();
    }

    public function init(): void {
        add_action('admin_post_studiofy_save_client', [$this, 'handle_save']);
        add_action('admin_post_studiofy_delete_client', [$this, 'handle_delete']);
    }

    public function render_page(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'studiofy_clients';
        
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $where = '';
        if ($search) {
            $where = $wpdb->prepare("WHERE first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR company LIKE %s", "%$search%", "%$search%", "%$search%", "%$search%");
        }

        $items = $wpdb->get_results("SELECT *, (SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_projects WHERE client_id = {$table}.id) as project_count, (SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_invoices WHERE client_id = {$table}.id) as invoice_count FROM $table $where ORDER BY created_at DESC");

        ?>
        <div class="wrap studiofy-dark-theme">
            <h1>Clients <button id="btn-new-client" class="page-title-action">New Client</button></h1>
            
            <form method="get">
                <input type="hidden" name="page" value="studiofy-clients" />
                <p class="search-box">
                    <label class="screen-reader-text" for="client-search-input">Search Clients:</label>
                    <input type="search" id="client-search-input" name="s" value="<?php echo esc_attr($search); ?>">
                    <input type="submit" id="search-submit" class="button" value="Search Clients">
                </p>
            </form>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="manage-column">Name</th>
                        <th class="manage-column">Contact</th>
                        <th class="manage-column">Company</th>
                        <th class="manage-column">Status</th>
                        <th class="manage-column">Projects</th>
                        <th class="manage-column">Invoices</th>
                        <th class="manage-column">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="7">No clients found.</td></tr>
                    <?php else: foreach ($items as $item): 
                        $del_url = wp_nonce_url(admin_url('admin_post.php?action=studiofy_delete_client&id=' . $item->id), 'delete_client_' . $item->id);
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
                                <a href="<?php echo $del_url; ?>" onclick="return confirm('Delete this client?');" class="button button-small" style="color:#a00;">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div id="modal-new-client" class="studiofy-modal-overlay studiofy-hidden">
            <div class="studiofy-modal">
                <div class="studiofy-modal-header">
                    <h2>New Client</h2>
                    <button type="button" class="close-modal">&times;</button>
                </div>
                <div class="studiofy-modal-body">
                    <form method="post" action="<?php echo admin_url('admin_post.php'); ?>">
                        <input type="hidden" name="action" value="studiofy_save_client">
                        <?php wp_nonce_field('save_client', 'studiofy_nonce'); ?>
                        
                        <div class="studiofy-form-row">
                            <div class="studiofy-col"><label>First Name *</label><input type="text" name="first_name" required class="widefat"></div>
                            <div class="studiofy-col"><label>Email *</label><input type="email" name="email" required class="widefat"></div>
                        </div>
                        <div class="studiofy-form-row">
                            <div class="studiofy-col"><label>Last Name</label><input type="text" name="last_name" class="widefat"></div>
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
                            <button type="submit" class="button button-primary">Create Client</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($){
                $('#btn-new-client').click(function(e){ e.preventDefault(); $('#modal-new-client').removeClass('studiofy-hidden'); });
                $('.close-modal').click(function(e){ e.preventDefault(); $('#modal-new-client').addClass('studiofy-hidden'); });
            });
        </script>
        <?php
    }

    public function handle_save(): void {
        check_admin_referer('save_client', 'studiofy_nonce');
        global $wpdb;
        
        $wpdb->insert($wpdb->prefix.'studiofy_clients', [
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => $this->encryption->encrypt(sanitize_text_field($_POST['phone'])), // Encrypt
            'company' => sanitize_text_field($_POST['company']),
            'address' => $this->encryption->encrypt(sanitize_text_field($_POST['address'])), // Encrypt
            'notes' => sanitize_textarea_field($_POST['notes']),
            'status' => sanitize_text_field($_POST['status'])
        ]);
        
        wp_redirect(admin_url('admin.php?page=studiofy-clients'));
        exit;
    }

    public function handle_delete(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        check_admin_referer('delete_client_' . $_GET['id']);
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'studiofy_clients', ['id' => (int) $_GET['id']]);
        wp_redirect(admin_url('admin.php?page=studiofy-clients'));
        exit;
    }
}
