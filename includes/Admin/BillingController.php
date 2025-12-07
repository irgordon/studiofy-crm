<?php
/**
 * Billing Controller (Unified Contract & Invoice)
 * @package Studiofy\Admin
 * @version 2.3.3
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
                    <td>" . esc_html($r->id) . "</td>
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
        
        if ($id) {
            $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_invoices WHERE id = %d", $id));
            $line_items = json_decode($data->line_items, true) ?: [];
        } else {
            // New Record Defaults
            $data = new \stdClass();
            $data->id = 0;
            $data->customer_id = 0;
            $data->title = '';
            $data->service_type = 'Portrait'; // Default suggestion
            $data->amount = 0.00;
            $data->tax_amount = 0.00;
            $data->service_fee = 0.00; // Value stored as amount
            $data->deposit_amount = 0.00;
            $data->status = 'Draft';
            $data->contract_status = 'Unsigned';
            $data->contract_body = '';
            $data->payment_methods = '[]';
            $data->memo = "Thank you for your business!";
            $data->due_date = date('Y-m-d', strtotime('+30 days'));
            
            // Default Item for New Billing
            $line_items = [
                ['desc' => 'Photography Session', 'rate' => 0.00, 'qty' => 1]
            ];
        }

        $customers = $wpdb->get_results("SELECT id, first_name, last_name FROM {$wpdb->prefix}studiofy_customers ORDER BY last_name ASC");
        
        // Prepare template variables
        $active_methods = json_decode($data->payment_methods ?? '[]', true) ?: [];
        
        require_once STUDIOFY_PATH . 'templates/admin/billing-builder.php';
    }

    public function handle_save(): void {
        check_admin_referer('save_billing', 'studiofy_nonce');
        global $wpdb;
        
        // 1. Line Items
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
        
        // 2. Calculations
        // Discount (New)
        $discount_percent = isset($_POST['discount_percent']) ? (float)$_POST['discount_percent'] : 0;
        $discount_amount = $subtotal * ($discount_percent / 100);
        $taxable_amount = max(0, $subtotal - $discount_amount);

        // Tax
        $tax_rate = isset($_POST['tax_rate']) ? (float)$_POST['tax_rate'] : 0;
        $tax_amt = $taxable_amount * ($tax_rate / 100);
        
        // Service Fee (3%)
        $service_fee = 0.00;
        if (isset($_POST['apply_service_fee'])) {
            $service_fee = $taxable_amount * 0.03;
        }

        // Tip (5, 10, 20%) - Just save the amount if calculated, or flag it?
        // Usually tips are added BY customer at payment. 
        // Here we might just be setting a "Tip Amount" if manually added, or enabling the option.
        // For this logic, we'll save any pre-calculated tip amount if passed, otherwise 0.
        $tip_amount = isset($_POST['tip_amount']) ? (float)$_POST['tip_amount'] : 0.00;

        $total = $taxable_amount + $tax_amt + $service_fee + $tip_amount;

        $db_data = [
            'customer_id' => (int)$_POST['customer_id'],
            'title' => sanitize_text_field($_POST['title']),
            'service_type' => sanitize_text_field($_POST['service_type']), // Custom Text Input
            'contract_body' => wp_kses_post($_POST['contract_body']), 
            'contract_status' => sanitize_text_field($_POST['contract_status']),
            'amount' => $total,
            'tax_amount' => $tax_amt,
            'service_fee' => $service_fee, // Saving actual fee amount
            'deposit_amount' => (float)$_POST['deposit_amount'],
            'payment_methods' => json_encode($_POST['payment_methods'] ?? []),
            'line_items' => json_encode($cleaned_items),
            'due_date' => sanitize_text_field($_POST['final_due_date']),
            'memo' => sanitize_textarea_field($_POST['memo']),
            'status' => sanitize_text_field($_POST['payment_status'])
        ];
        
        // Note: You might want to save 'discount_amount' and 'tip_amount' to DB if you add columns later.
        // For now, they affect 'amount' (Total).

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
