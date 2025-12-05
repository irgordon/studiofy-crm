<?php
/**
 * Client Management with Native Table
 * @package Studiofy\Admin
 * @version 2.0.1
 */
declare(strict_types=1);

namespace Studiofy\Admin;

use Studiofy\Utils\TableHelper;

class ClientController {
    use TableHelper;

    public function init(): void {
        add_action('admin_post_studiofy_delete_client', [$this, 'handle_delete']);
    }

    public function render_page(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'studiofy_clients';

        $orderby = $_GET['orderby'] ?? 'created_at';
        $order   = strtoupper($_GET['order'] ?? 'DESC');
        
        // Security Whitelist
        $allowed = ['first_name', 'last_name', 'email', 'status', 'created_at'];
        if (!in_array($orderby, $allowed)) $orderby = 'created_at';
        if (!in_array($order, ['ASC', 'DESC'])) $order = 'DESC';

        $items = $wpdb->get_results("SELECT * FROM $table ORDER BY $orderby $order");

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Clients</h1>
            <hr class="wp-header-end">
            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <th class="manage-column sortable"><?php echo $this->sort_link('Last Name', 'last_name'); ?></th>
                        <th class="manage-column sortable"><?php echo $this->sort_link('First Name', 'first_name'); ?></th>
                        <th class="manage-column sortable"><?php echo $this->sort_link('Email', 'email'); ?></th>
                        <th class="manage-column sortable"><?php echo $this->sort_link('Status', 'status'); ?></th>
                        <th class="manage-column">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="5">No clients found.</td></tr>
                    <?php else: foreach ($items as $item): 
                        $del_url  = wp_nonce_url(admin_url('admin_post.php?action=studiofy_delete_client&id=' . $item->id), 'delete_client_' . $item->id);
                    ?>
                        <tr>
                            <td><?php echo esc_html($item->last_name); ?></td>
                            <td><?php echo esc_html($item->first_name); ?></td>
                            <td><?php echo esc_html($item->email); ?></td>
                            <td><span class="studiofy-badge <?php echo esc_attr($item->status); ?>"><?php echo esc_html(ucfirst($item->status)); ?></span></td>
                            <td><a href="<?php echo $del_url; ?>" onclick="return confirm('Delete this client?');" class="button button-small">Delete</a></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function handle_delete(): void {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        $id = (int) $_GET['id'];
        check_admin_referer('delete_client_' . $id);

        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'studiofy_clients', ['id' => $id]);
        
        wp_redirect(admin_url('admin.php?page=studiofy-clients&msg=deleted'));
        exit;
    }
}
