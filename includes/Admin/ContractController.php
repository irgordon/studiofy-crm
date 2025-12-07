<?php
/**
 * Contract Controller
 * @package Studiofy\Admin
 * @version 2.2.30
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
        
        // Use default template if this is a new contract
        $default_content = $this->get_default_contract_template();

        if ($linked_post_id === 0) {
            $post_id = wp_insert_post([
                'post_title' => 'Contract: ' . sanitize_text_field($_POST['title']),
                'post_type'  => 'studiofy_doc',
                'post_status' => 'publish',
                'post_content' => $default_content // Inject Template Here
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
            'body_content' => $default_content, // Also save to DB for fallback
            'linked_post_id' => $linked_post_id
        ];

        if (!empty($_POST['contract_id'])) {
            // Don't overwrite body content on update if it exists
            unset($data['body_content']);
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

    /**
     * Default Contract Template (Matches uploaded DOCX)
     */
    private function get_default_contract_template(): string {
        return '
        <div class="studiofy-contract-template">
            <h2 style="text-align: center;">PHOTOGRAPHY CONTRACT</h2>
            
            <h3>1. The Parties</h3>
            <p>This Service Contract (the “Agreement”) made on [MM/DD/YYYY] (the “Effective Date”) is by and between:</p>
            <ul>
                <li><strong>Photographer:</strong> [SERVICE PROVIDER NAME], with a mailing address of [SERVICE PROVIDER ADDRESS] (the “Service Provider”).</li>
                <li><strong>Client:</strong> [CLIENT NAME], with a mailing address of [CLIENT ADDRESS] (the “Client”).</li>
            </ul>
            <p>The Service Provider and the Client are each referred to as a “Party” and, collectively, as the “Parties.”</p>

            <h3>2. Term</h3>
            <p>The term of this Agreement shall commence on [MM/DD/YYYY] and terminate upon completion of the Services performed.</p>

            <h3>3. Services</h3>
            <p>The Service Provider agrees to provide the following Services:</p>
            <p>[DESCRIBE SERVICES TO BE PERFORMED]</p>
            <p>The address where the services will be completed is: [ENTER ADDRESS]</p>

            <h3>4. Payment Amount</h3>
            <p>The Client agrees to pay the Service Provider the following compensation:</p>
            <p><strong>Total Amount:</strong> $[RATE]</p>

            <h3>5. Retainer</h3>
            <p>The Client is REQUIRED to pay a Retainer in the amount of $[RETAINER AMOUNT] to the Service Provider as an advance on future Services. This Retainer is Non-Refundable.</p>

            <h3>6. Copyright and Ownership</h3>
            <p>All photographs taken by the Service Provider shall remain the property of the Service Provider and may not be used, reproduced, or distributed without the Service Provider’s written consent. The Client may use the photographs for personal, non-commercial purposes.</p>

            <h3>7. Model Release</h3>
            <p>The Service Provider shall have the right to use the photographs taken for advertising, marketing, and other promotional purposes.</p>

            <h3>8. Liability & Mutual Indemnification</h3>
            <p>If the Service Provider is unable to perform the services due to any cause outside of their control (fire, flood, illness, etc.), the Client agrees to indemnify the Service Provider for any loss damage or liability; however, the Service Provider will return all payments made by the Client.</p>
            <p>Each Party shall indemnify, hold harmless, and defend the other Party against any and all losses, damages, liabilities, or claims arising out of the Services provided under this Agreement.</p>

            <h3>9. Independent Contractor Status</h3>
            <p>The Service Provider is an independent contractor and not an employee of the Client. The Service Provider has the sole right to control and direct the means, manner, and method by which the Services will be performed.</p>

            <h3>10. Governing Law</h3>
            <p>This Agreement shall be governed under the laws in the State of [STATE NAME].</p>

            <h3>11. Entire Agreement</h3>
            <p>This Agreement constitutes the entire agreement between the Parties and supersedes all prior agreements. No modification shall be binding unless executed in writing by the Parties.</p>
        </div>';
    }
}
