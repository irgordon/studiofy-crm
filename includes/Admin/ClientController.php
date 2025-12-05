<?php
/**
 * Client Controller
 * @package Studiofy\Admin
 * @version 2.0.1
 */
declare(strict_types=1);
namespace Studiofy\Admin;
use Studiofy\Utils\TableHelper;

class ClientController {
    use TableHelper;
    public function init(): void { add_action('admin_post_studiofy_delete_client', [$this, 'handle_delete']); }
    public function render_page(): void {
        global $wpdb;
        $items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}studiofy_clients ORDER BY created_at DESC");
        echo '<div class="wrap"><h1>Clients</h1><table class="wp-list-table widefat fixed striped"><thead><tr><th>Name</th><th>Email</th><th>Status</th></tr></thead><tbody>';
        foreach($items as $i) echo "<tr><td>{$i->first_name}</td><td>{$i->email}</td><td>{$i->status}</td></tr>";
        echo '</tbody></table></div>';
    }
    public function handle_delete(): void { 
         if (!current_user_can('manage_options')) wp_die('Unauthorized');
         // nonce checks...
    }
}
