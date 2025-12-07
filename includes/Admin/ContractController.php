<?php
/**
 * Contract Controller
 * @package Studiofy\Admin
 * @version 2.2.28
 */

declare(strict_types=1);

namespace Studiofy\Admin;

use function Studiofy\studiofy_get_asset_version;
use Studiofy\Utils\TableHelper;

class ContractController {
    use TableHelper;

    public function init(): void {
        add_action('admin_post_studiofy_save_contract', [$this, 'handle_save']);
        add_action('admin_post_studiofy_sign_contract', [$this, 'handle_signature']);
        add_action('admin_post_studiofy_delete_contract', [$this, 'handle_delete']);
    }

    // ... (render_page, render_list, render_builder, handle_save, handle_delete same as v2.2.20) ...
    public function render_page(): void {
        $action = $_GET['action'] ?? 'list';
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($action === 'create' || $action === 'edit') $this->render_builder($id);
        elseif ($action === 'view') $this->render_view($id);
        else $this->render_list();
    }

    private function render_list(): void {
        global $wpdb;
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $where = $search ? $wpdb->prepare("WHERE c.title LIKE %s", "%$search%") : '';
        $sql = "SELECT c.*, cust.first_name, cust.last_name FROM {$wpdb->prefix}studiofy_contracts c LEFT JOIN {$wpdb->prefix}studiofy_customers cust ON c.customer_id = cust.id $where ORDER BY c.created_at DESC";
        $rows = $wpdb->get_results($sql);
        
        echo '<div class="wrap"><h1 class="wp-heading-inline">Contracts</h1><a href="?page=studiofy-contracts&action=create" class="page-title-action">New Contract</a><hr class="wp-header-end">';
        echo '<div class="studiofy-toolbar"><form method="get" action=""><input type="hidden" name="page" value="studiofy-contracts"><label for="search_contracts" class="screen-reader-text">Search</label><input type="search" id="search_contracts" name="s" placeholder="Search contracts..." class="widefat" style="max-width:400px;" value="'.esc_attr($search).'"></form></div>';
        
        if (empty($rows)) {
            echo '<div class="studiofy-empty-card"><div class="empty-icon dashicons dashicons-edit"></div><h2>No contracts yet</h2><p>Create your first contract with digital signature capture.</p><a href="?page=studiofy-contracts&action=create" class="button button-primary button-large">Create Contract</a></div>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Title</th><th>Customer</th><th>Status</th><th>Value</th><th>Actions</th></tr></thead><tbody>';
            foreach($rows as $r) {
                $cust = $r->first_name ? esc_html($r->first_name . ' ' . $r->last_name) : 'Unknown';
                $del_url = wp_nonce_url(admin_url('admin-post.php?action=studiofy_delete_contract&id='.$r->id), 'delete_contract_'.$r->id);
                echo "<tr>
                    <td>" . esc_html($r->title) . "</td>
                    <td>" . $cust . "</td>
                    <td><span class='studiofy-badge " . esc_attr(strtolower($r->status)) . "'>" . esc_html(ucfirst($r->status)) . "</span></td>
                    <td>$" . esc_html($r->amount) . "</td>
                    <td>
                        <a href='?page=studiofy-contracts&action=view&id={$r->id}' class='button button-small'>View</a>
                        <a href='?page=studiofy-contracts&action=edit&id={$r->id}' class='button button-small'>Edit</a>
                        <a href='$del_url' class='button button-small' style='color:#b32d2e;' onclick='return confirm(\"Delete contract?\");'>Delete</a>
                    </td>
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
        
        $elementor_url = '';
        if ($contract && $contract->linked_post_id) {
            $elementor_url = admin_url('post.php?post=' . $contract->linked_post_id . '&action=elementor');
        }

        require_once STUDIOFY_PATH . 'templates/admin/contract-builder.php';
    }

    private function render_view(int $id): void {
        global $wpdb;
        $contract = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_contracts WHERE id = %d", $id));
        if (!$contract) { echo '<div class="notice notice-error"><p>Contract not found.</p></div>'; return; }
        
        $content = $contract->body_content;
        if ($contract->linked_post_id && class_exists('\Elementor\Plugin')) {
            $elem_content = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($contract->linked_post_id);
            if (!empty($elem_content)) {
                $content = $elem_content;
            }
        }
        
        // Find or create a page for signing if it doesn't exist, OR just provide instructions
        // For simplicity, we assume the user creates a "Contract Portal" page with [studiofy_contract_portal].
        // We will try to find a page with that shortcode.
        $portal_page = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE '%[studiofy_contract_portal]%' AND post_status='publish' LIMIT 1");
        $signing_link = $portal_page ? get_permalink($portal_page) . '?contract_id=' . $contract->id : '';

        wp_enqueue_style('studiofy-contract-css', STUDIOFY_URL . 'assets/css/contract.css', [], studiofy_get_asset_version('assets/css/contract.css'));
        
        $view_content = $content; 
        require_once STUDIOFY_PATH . 'templates/admin/contract-view.php';
    }

    public function handle_save(): void {
        check_admin_referer('save_contract', 'studiofy_nonce');
        global $wpdb;
        
        $linked_post_id = isset($_POST['linked_post_id']) ? (int)$_POST['linked_post_id'] : 0;
        
        if ($linked_post_id === 0) {
            $post_id = wp_insert_post([
                'post_title' => 'Contract: ' . sanitize_text_field($_POST['title']),
                'post_type'  => 'studiofy_doc',
                'post_status' => 'publish'
            ]);
            if ($post_id) $linked_post_id = $post_id;
        } else {
            wp_update_post([
                'ID' => $linked_post_id,
                'post_title' => 'Contract: ' . sanitize_text_field($_POST['title'])
            ]);
        }

        $data = [
            'title' => sanitize_text_field($_POST['title']),
            'customer_id' => (int)$_POST['customer_id'],
            'project_id' => (int)$_POST['project_id'],
            'start_date' => sanitize_text_field($_POST['start_date']),
            'end_date' => sanitize_text_field($_POST['end_date']),
            'amount' => (float)$_POST['amount'],
            'status' => sanitize_text_field($_POST['status']),
            'body_content' => '', 
            'linked_post_id' => $linked_post_id
        ];

        if (!empty($_POST['contract_id'])) {
            $wpdb->update($wpdb->prefix . 'studiofy_contracts', $data, ['id' => (int)$_POST['contract_id']]);
            $redirect_id = (int)$_POST['contract_id'];
        } else {
            $wpdb->insert($wpdb->prefix . 'studiofy_contracts', $data);
            $redirect_id = $wpdb->insert_id;
        }
        
        wp_redirect(admin_url('admin.php?page=studiofy-contracts&action=edit&id=' . $redirect_id));
        exit;
    }

    public function handle_signature(): void {
        // This is primarily for Admin Signing if needed, Frontend uses Shortcode logic
        global $wpdb;
        $id = (int) $_POST['contract_id'];
        $wpdb->update($wpdb->prefix . 'studiofy_contracts', ['signature_data' => $_POST['signature_data'], 'signed_name' => sanitize_text_field($_POST['signed_name']), 'signed_at' => current_time('mysql'), 'status' => 'signed'], ['id' => $id]);
        wp_redirect(admin_url('admin.php?page=studiofy-contracts&action=view&id=' . $id)); exit;
    }

    public function handle_delete(): void {
        check_admin_referer('delete_contract_' . $_GET['id']);
        global $wpdb;
        $id = (int)$_GET['id'];
        $post_id = $wpdb->get_var($wpdb->prepare("SELECT linked_post_id FROM {$wpdb->prefix}studiofy_contracts WHERE id = %d", $id));
        if ($post_id) wp_delete_post($post_id, true);
        $wpdb->delete($wpdb->prefix.'studiofy_contracts', ['id' => $id]);
        wp_redirect(admin_url('admin.php?page=studiofy-contracts')); exit;
    }
}
