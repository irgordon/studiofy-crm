<?php
/**
 * Contract Controller
 * @package Studiofy\Admin
 * @version 2.0.1
 */

declare(strict_types=1);

namespace Studiofy\Admin;

use function Studiofy\studiofy_get_asset_version;

class ContractController {

    public function init(): void {
        add_action('admin_post_studiofy_save_contract', [$this, 'handle_save']);
        add_action('admin_post_studiofy_sign_contract', [$this, 'handle_signature']);
    }

    public function render_page(): void {
        $action = $_GET['action'] ?? 'list';
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($action === 'create' || $action === 'edit') {
            $this->render_builder($id);
        } elseif ($action === 'view') {
            $this->render_view($id);
        } else {
            $this->render_list();
        }
    }

    private function render_list(): void {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}studiofy_contracts ORDER BY created_at DESC");
        echo '<div class="wrap"><h1>Contracts</h1><a href="?page=studiofy-contracts&action=create" class="page-title-action">New Contract</a>';
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Title</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
        foreach($rows as $r) {
            echo "<tr><td>{$r->title}</td><td>{$r->status}</td><td><a href='?page=studiofy-contracts&action=view&id={$r->id}'>Sign/View</a></td></tr>";
        }
        echo '</tbody></table></div>';
    }

    private function render_builder(int $id): void {
        global $wpdb;
        $contract = ($id > 0) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_contracts WHERE id = %d", $id)) : null;
        require_once STUDIOFY_PATH . 'templates/admin/contract-builder.php';
    }

    private function render_view(int $id): void {
        global $wpdb;
        $contract = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_contracts WHERE id = %d", $id));
        if(!$contract) return;
        
        wp_enqueue_script('studiofy-signature', STUDIOFY_URL . 'assets/js/signature-pad.js', [], studiofy_get_asset_version('assets/js/signature-pad.js'), true);
        wp_enqueue_style('studiofy-contract-css', STUDIOFY_URL . 'assets/css/contract.css', [], studiofy_get_asset_version('assets/css/contract.css'));
        
        require_once STUDIOFY_PATH . 'templates/admin/contract-view.php';
    }

    public function handle_save(): void { /* Save Logic from v1.0 */ }
    public function handle_signature(): void { /* Signature Logic from v1.0 */ }
}
