<?php
/**
 * Billing Controller (Unified Contract & Invoice)
 * @package Studiofy\Admin
 * @version 2.3.0
 */

declare(strict_types=1);

namespace Studiofy\Admin;

use Studiofy\Utils\TableHelper;
use Studiofy\Security\Encryption;

class BillingController {
    use TableHelper;

    public function init(): void {
        add_action('admin_post_studiofy_save_billing', [$this, 'handle_save']);
        add_action('admin_post_studiofy_delete_billing', [$this, 'handle_delete']);
    }

    public function render_page(): void {
        $action = $_GET['action'] ?? 'list';
        if ($action === 'create' || $action === 'edit') {
            $this->render_builder();
        } else {
            $this->render_list();
        }
    }

    private function render_list(): void {
        global $wpdb;
        $orderby = $_GET['orderby'] ?? 'id';
        $order = strtoupper($_GET['order'] ?? 'DESC');
        
        $sql = "SELECT i.*, c.first_name, c.last_name FROM {$wpdb->prefix}studiofy_invoices i LEFT JOIN {$wpdb->prefix}studiofy_customers c ON i.customer_id = c.id ORDER BY i.$orderby $order";
        $rows = $wpdb->get_results($sql);
        
        echo '<div class="wrap"><h1 class="wp-heading-inline">Billing & Contracts</h1>';
        echo '<a href="?page=studiofy-billing&action=create" class="page-title-action">Create New</a>';
        echo '<hr class="wp-header-end">';
        
        if (empty($rows)) {
            echo '<div class="studiofy-empty-card"><h2>No billing records found</h2><a href="?page=studiofy-billing&action=create" class="button button-primary">Create First Record</a></div>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
            echo '<th>ID</th><th>Customer Name</th><th>Contract Title</th><th>Service Type</th><th>Amount Billed</th><th>Payment Status</th><th>Contract Status</th><th>Actions</th>';
            echo '</tr></thead><tbody>';
            
            foreach ($rows as $r) {
                $customer = $r->first_name ? esc_html($r->first_name . ' ' . $r->last_name) : 'Unknown';
                $edit_url = "?page=studiofy-billing&action=edit&id={$r->id}";
                $del_url = wp_nonce_url(admin_url("admin-post.php?action=studiofy_delete_billing&id={$r->id}"), 'delete_billing_'.$r->id);
                
                echo "<tr>
                    <td>" . esc_html($r->customer_id) . "</td>
                    <td><strong>" . $customer . "</strong></td>
                    <td>" . esc_html($r->title) . "</td>
                    <td>" . esc_html($r->service_type) . "</td>
                    <td>$" . number_format((float)$r->amount, 2) . "</td>
                    <td><span class='studiofy-badge " . esc_attr(strtolower($r->status)) . "'>" . esc_html($r->status) . "</span></td>
                    <td><span class='studiofy-badge " . esc_attr(strtolower($r->contract_status)) . "'>" . esc_html($r->contract_status) . "</span></td>
                    <td>
                        <a href='$edit_url' class='button button-small'>Edit</a>
                        <a href='$del_url' class='button button-small button-link-delete' onclick='return confirm(\"Delete?\")'>Delete</a>
                    </td>
                </tr>";
            }
            echo '</tbody></table>';
        }
        echo '</div>';
    }

    private function render_builder(): void {
        global $wpdb;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        // Initialize Data
        if ($id) {
            $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_invoices WHERE id = %d", $id));
        } else {
            $data = new \stdClass();
            $data->id = 0;
            $data->customer_id = 0;
            $data->title = '';
            $data->service_type = 'Portrait';
            $data->amount = 0.00;
            $data->tax_amount = 0.00;
            $data->service_fee = 0.00;
            $data->deposit_amount = 0.00;
            $data->status = 'Draft';
            $data->contract_status = 'Unsigned';
            $data->contract_body = '';
            $data->line_items = '[]';
            $data->payment_methods = '[]';
            $data->memo = "Thank you for your business!";
            $data->due_date = date('Y-m-d', strtotime('+30 days'));
        }

        $customers = $wpdb->get_results("SELECT id, first_name, last_name FROM {$wpdb->prefix}studiofy_customers ORDER BY last_name ASC");
        
        // Prepare template variables
        $line_items = json_decode($data->line_items, true) ?: [];
        $active_methods = json_decode($data->payment_methods, true) ?: [];
        
        require_once STUDIOFY_PATH . 'templates/admin/billing-builder.php';
    }

    public function handle_save(): void {
        check_admin_referer('save_billing', 'studiofy_nonce');
        global $wpdb;
        
        // Parse Line Items
        $items = $_POST['items'] ?? [];
        $subtotal = 0;
        $cleaned_items = [];
        if (is_array($items)) {
            foreach($items as $i) {
                $qty = (float)$i['qty'];
                $rate = (float)$i['rate'];
                $desc = sanitize_text_field($i['name']);
                $subtotal += ($qty * $rate);
                $cleaned_items[] = ['desc'=>$desc, 'qty'=>$qty, 'rate'=>$rate];
            }
        }
        
        $tax_rate = isset($_POST['tax_rate']) ? (float)$_POST['tax_rate'] : 0;
        $tax_amt = $subtotal * ($tax_rate / 100);
        $service_fee = isset($_POST['enable_tipping']) ? ($subtotal * 0.03) : 0.00;
        $total = $subtotal + $tax_amt + $service_fee;

        $db_data = [
            'customer_id' => (int)$_POST['customer_id'],
            'title' => sanitize_text_field($_POST['title']),
            'service_type' => sanitize_text_field($_POST['service_type']),
            'contract_body' => wp_kses_post($_POST['contract_body']), // Content from wp_editor
            'contract_status' => sanitize_text_field($_POST['contract_status']),
            'amount' => $total,
            'tax_amount' => $tax_amt,
            'service_fee' => $service_fee,
            'deposit_amount' => (float)$_POST['deposit_amount'],
            'payment_methods' => json_encode($_POST['payment_methods'] ?? []),
            'line_items' => json_encode($cleaned_items),
            'due_date' => sanitize_text_field($_POST['final_due_date']),
            'memo' => sanitize_textarea_field($_POST['memo']),
            'status' => sanitize_text_field($_POST['payment_status'])
        ];

        if(!empty($_POST['id'])) {
            $wpdb->update($wpdb->prefix.'studiofy_invoices', $db_data, ['id'=>(int)$_POST['id']]);
        } else {
            $wpdb->insert($wpdb->prefix.'studiofy_invoices', array_merge($db_data, ['created_at' => current_time('mysql'), 'invoice_number' => 'INV-'.rand(1000,9999)]));
        }
        
        wp_redirect(admin_url('admin.php?page=studiofy-billing'));
        exit;
    }

    public function handle_delete(): void {
        check_admin_referer('delete_billing_'.$_GET['id']);
        global $wpdb;
        $wpdb->delete($wpdb->prefix.'studiofy_invoices', ['id'=>(int)$_GET['id']]);
        wp_redirect(admin_url('admin.php?page=studiofy-billing')); exit;
    }
}
