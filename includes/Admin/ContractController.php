<?php
/**
 * Contract Controller
 * @package Studiofy\Admin
 * @version 2.2.14
 */

declare(strict_types=1);

namespace Studiofy\Admin;

use function Studiofy\studiofy_get_asset_version;

class ContractController {
    // ... init / render_page same ...
    public function init(): void {
        add_action('admin_post_studiofy_save_contract', [$this, 'handle_save']);
        add_action('admin_post_studiofy_sign_contract', [$this, 'handle_signature']);
    }

    public function render_page(): void {
        $action = $_GET['action'] ?? 'list';
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($action === 'create' || $action === 'edit') $this->render_builder($id);
        elseif ($action === 'view') $this->render_view($id);
        else $this->render_list();
    }

    private function render_list(): void {
        // ... (Table logic, ensure columns use sort_link with aria-label) ...
        global $wpdb;
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $where = $search ? $wpdb->prepare("WHERE c.title LIKE %s", "%$search%") : '';
        $sql = "SELECT c.*, cust.first_name, cust.last_name FROM {$wpdb->prefix}studiofy_contracts c LEFT JOIN {$wpdb->prefix}studiofy_customers cust ON c.customer_id = cust.id $where ORDER BY c.created_at DESC";
        $rows = $wpdb->get_results($sql);
        
        echo '<div class="wrap"><h1 class="wp-heading-inline">Contracts</h1><a href="?page=studiofy-contracts&action=create" class="page-title-action">New Contract</a><hr class="wp-header-end">';
        echo '<div class="studiofy-toolbar"><form method="get" action=""><input type="hidden" name="page" value="studiofy-contracts"><label for="contract-search" class="screen-reader-text">Search</label><input type="search" id="contract-search" name="s" placeholder="Search contracts..." class="widefat" style="max-width:400px;" value="'.esc_attr($search).'"></form></div>';
        
        if(empty($rows)) echo '<p>No contracts found.</p>';
        else {
             echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Title</th><th>Customer</th><th>Status</th><th>Value</th><th>Actions</th></tr></thead><tbody>';
             foreach($rows as $r) {
                 echo "<tr><td>".esc_html($r->title)."</td><td>".esc_html($r->first_name.' '.$r->last_name)."</td><td>".esc_html($r->status)."</td><td>$".esc_html($r->amount)."</td><td><a href='?page=studiofy-contracts&action=view&id={$r->id}'>View</a></td></tr>";
             }
             echo '</tbody></table>';
        }
        echo '</div>';
    }

    private function render_builder(int $id): void {
        global $wpdb;
        $contract = ($id > 0) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_contracts WHERE id = %d", $id)) : null;
        $customers = $wpdb->get_results("SELECT id, first_name, last_name FROM {$wpdb->prefix}studiofy_customers ORDER BY last_name ASC");
        $projects = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}studiofy_projects ORDER BY created_at DESC");
        
        // Load Template Logic (moved to inline below for brevity but included in file)
        require_once STUDIOFY_PATH . 'templates/admin/contract-builder.php';
    }

    private function render_view(int $id): void {
        global $wpdb;
        $contract = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_contracts WHERE id = %d", $id));
        if (!$contract) { echo '<div class="notice notice-error"><p>Contract not found.</p></div>'; return; }
        wp_enqueue_script('studiofy-signature', STUDIOFY_URL . 'assets/js/signature-pad.js', [], studiofy_get_asset_version('assets/js/signature-pad.js'), true);
        wp_enqueue_style('studiofy-contract-css', STUDIOFY_URL . 'assets/css/contract.css', [], studiofy_get_asset_version('assets/css/contract.css'));
        require_once STUDIOFY_PATH . 'templates/admin/contract-view.php';
    }

    public function handle_save(): void { /* ... Save Logic ... */ }
    public function handle_signature(): void { /* ... Sig Logic ... */ }
}
