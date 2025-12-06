<?php
/**
 * Contract Controller
 * @package Studiofy\Admin
 * @version 2.1.2
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
        
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $where = '';
        if ($search) {
            $where = $wpdb->prepare("WHERE c.title LIKE %s", "%$search%");
        }

        $sql = "SELECT c.*, cust.first_name, cust.last_name 
                FROM {$wpdb->prefix}studiofy_contracts c
                LEFT JOIN {$wpdb->prefix}studiofy_customers cust ON c.customer_id = cust.id 
                $where
                ORDER BY c.created_at DESC";
                
        $rows = $wpdb->get_results($sql);
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_contracts");
        
        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">Contracts</h1>';
        echo '<a href="?page=studiofy-contracts&action=create" class="page-title-action">New Contract</a>';
        echo '<hr class="wp-header-end">';
        
        echo '<div class="studiofy-toolbar">';
        echo '<form method="get" action="">';
        echo '<input type="hidden" name="page" value="studiofy-contracts">';
        echo '<input type="search" name="s" placeholder="Search contracts by title..." class="widefat" style="max-width:400px;" value="'.esc_attr($search).'">';
        echo '</form>';
        echo '</div>';

        if ($count == 0 && empty($search)) {
            echo '<div class="studiofy-empty-state">';
            echo '<div class="empty-icon dashicons dashicons-edit"></div>';
            echo '<h2>No contracts yet</h2>';
            echo '<p>Create your first contract with digital signature capture.</p>';
            echo '<a href="?page=studiofy-contracts&action=create" class="button button-primary button-large">Create Contract</a>';
            echo '</div>';
        } elseif (empty($rows)) {
            echo '<p>No contracts found.</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Title</th><th>Customer</th><th>Status</th><th>Value</th><th>Actions</th></tr></thead><tbody>';
            foreach($rows as $r) {
                $customer_name = $r->first_name ? esc_html($r->first_name . ' ' . $r->last_name) : 'Unknown';
                echo "<tr>
                    <td>" . esc_html($r->title) . "</td>
                    <td>" . $customer_name . "</td>
                    <td><span class='studiofy-badge " . esc_attr(strtolower($r->status)) . "'>" . esc_html(ucfirst($r->status)) . "</span></td>
                    <td>$" . esc_html($r->amount) . "</td>
                    <td><a href='?page=studiofy-contracts&action=view&id={$r->id}' class='button button-small'>View</a></td>
                </tr>";
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
        
        require_once STUDIOFY_PATH . 'templates/admin/contract-builder.php';
    }

    private function render_view(int $id): void {
        global $wpdb;
        $contract = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_contracts WHERE id = %d", $id));
        
        if (!$contract) {
            echo '<div class="notice notice-error"><p>Contract not found.</p></div>';
            return;
        }
        
        wp_enqueue_script('studiofy-signature', STUDIOFY_URL . 'assets/js/signature-pad.js', [], studiofy_get_asset_version('assets/js/signature-pad.js'), true);
        wp_enqueue_style('studiofy-contract-css', STUDIOFY_URL . 'assets/css/contract.css', [], studiofy_get_asset_version('assets/css/contract.css'));
        
        require_once STUDIOFY_PATH . 'templates/admin/contract-view.php';
    }

    public function handle_save(): void {
        check_admin_referer('save_contract', 'studiofy_nonce');
        global $wpdb;
        
        $data = [
            'title' => sanitize_text_field($_POST['title']),
            'customer_id' => (int)$_POST['customer_id'],
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
