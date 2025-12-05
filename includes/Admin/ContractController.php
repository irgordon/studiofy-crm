<?php
/**
 * Contract Controller
 * @package Studiofy\Admin
 * @version 2.0.4
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
        
        echo '<div class="wrap studiofy-dark-theme">';
        echo '<h1>Contracts <a href="?page=studiofy-contracts&action=create" class="page-title-action">New Contract</a></h1>';
        
        if(empty($rows)) {
            echo '<div class="studiofy-empty-state"><p>No contracts yet.</p></div>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Title</th><th>Client</th><th>Status</th><th>Value</th><th>Actions</th></tr></thead><tbody>';
            foreach($rows as $r) {
                echo "<tr>
                    <td>" . esc_html($r->title) . "</td>
                    <td>" . esc_html($r->client_id) . "</td>
                    <td>" . esc_html($r->status) . "</td>
                    <td>$" . esc_html($r->amount) . "</td>
                    <td><a href='?page=studiofy-contracts&action=view&id={$r->id}'>View</a></td>
                </tr>";
            }
            echo '</tbody></table>';
        }
        echo '</div>';
    }

    private function render_builder(int $id): void {
        global $wpdb;
        $contract = ($id > 0) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_contracts WHERE id = %d", $id)) : null;
        $clients = $wpdb->get_results("SELECT id, first_name, last_name FROM {$wpdb->prefix}studiofy_clients");
        $projects = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}studiofy_projects");
        
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

    public function handle_save(): void {
        check_admin_referer('save_contract', 'studiofy_nonce');
        global $wpdb;
        
        $data = [
            'title' => sanitize_text_field($_POST['title']),
            'client_id' => (int)$_POST['client_id'],
            'project_id' => (int)$_POST['project_id'],
            'start_date' => sanitize_text_field($_POST['start_date']),
            'end_date' => sanitize_text_field($_POST['end_date']),
            'amount' => (float)$_POST['amount'],
            'status' => sanitize_text_field($_POST['status']),
            'body_content' => wp_kses_post($_POST['body_content']),
        ];

        if (!empty($_POST['contract_id'])) {
            $wpdb->update($wpdb->prefix . 'studiofy_contracts', $data, ['id' => (int)$_POST['contract_id']]);
        } else {
            $wpdb->insert($wpdb->prefix . 'studiofy_contracts', $data);
        }
        
        wp_redirect(admin_url('admin.php?page=studiofy-contracts'));
        exit;
    }

    public function handle_signature(): void {
        global $wpdb;
        $id = (int) $_POST['contract_id'];
        
        $wpdb->update($wpdb->prefix . 'studiofy_contracts', [
            'signature_data' => $_POST['signature_data'],
            'signed_name' => sanitize_text_field($_POST['signed_name']),
            'signed_at' => current_time('mysql'),
            'status' => 'signed'
        ], ['id' => $id]);
        
        wp_redirect(admin_url('admin.php?page=studiofy-contracts&action=view&id=' . $id));
        exit;
    }
}
