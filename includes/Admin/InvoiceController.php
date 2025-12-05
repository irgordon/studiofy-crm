<?php
/**
 * Invoice Controller
 * @package Studiofy\Admin
 * @version 2.0.5
 */

declare(strict_types=1);

namespace Studiofy\Admin;

class InvoiceController {

    public function init(): void {
        add_action('admin_post_studiofy_save_invoice', [$this, 'handle_save']);
    }

    public function render_page(): void {
        $action = $_GET['action'] ?? 'list';
        if ($action === 'create') {
            $this->render_builder();
        } else {
            $this->render_list();
        }
    }

    private function render_list(): void {
        global $wpdb;
        // Join with Customers for display
        $sql = "SELECT i.*, c.first_name, c.last_name 
                FROM {$wpdb->prefix}studiofy_invoices i
                LEFT JOIN {$wpdb->prefix}studiofy_customers c ON i.customer_id = c.id 
                ORDER BY i.created_at DESC";
                
        $rows = $wpdb->get_results($sql);
        
        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">Invoices</h1>';
        echo '<a href="?page=studiofy-invoices&action=create" class="page-title-action">New Invoice</a>';
        echo '<hr class="wp-header-end">';
        
        if (empty($rows)) {
            echo '<div class="studiofy-empty-state"><p>No invoices yet.</p></div>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Number</th><th>Title</th><th>Customer</th><th>Amount</th><th>Status</th><th>Due Date</th><th>Actions</th></tr></thead><tbody>';
            foreach ($rows as $r) {
                $ical_link = rest_url("studiofy/v1/invoices/{$r->id}/ical");
                $customer_name = $r->first_name ? esc_html($r->first_name . ' ' . $r->last_name) : 'Unknown';
                
                echo "<tr>
                    <td>" . esc_html($r->invoice_number) . "</td>
                    <td>" . esc_html($r->title) . "</td>
                    <td>" . $customer_name . "</td>
                    <td>$" . esc_html($r->amount) . "</td>
                    <td><span class='studiofy-badge " . esc_attr(strtolower($r->status)) . "'>" . esc_html($r->status) . "</span></td>
                    <td>" . esc_html($r->due_date) . "</td>
                    <td>
                        <a href='" . esc_url($r->payment_link) . "' target='_blank' class='button button-small'>Pay</a>
                        <a href='" . esc_url($ical_link) . "' class='button button-small'>iCal</a>
                    </td>
                </tr>";
            }
            echo '</tbody></table>';
        }
        echo '</div>';
    }

    private function render_builder(): void {
        global $wpdb;
        // Fetch Customers
        $customers = $wpdb->get_results("SELECT id, first_name, last_name FROM {$wpdb->prefix}studiofy_customers ORDER BY last_name ASC");
        $projects = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}studiofy_projects ORDER BY created_at DESC");
        $inv_num = 'INV-' . strtoupper(uniqid());
        
        require_once STUDIOFY_PATH . 'templates/admin/invoice-builder.php';
    }

    public function handle_save(): void {
        check_admin_referer('save_invoice', 'studiofy_nonce');
        global $wpdb;
        
        $items = $_POST['items'] ?? [];
        $subtotal = 0;
        foreach($items as $i) {
            $subtotal += ((float)$i['qty'] * (float)$i['rate']);
        }
        
        $tax = (float)($_POST['tax_amount'] ?? 0);
        $total = $subtotal + $tax;

        $wpdb->insert($wpdb->prefix.'studiofy_invoices', [
            'invoice_number' => sanitize_text_field($_POST['invoice_number']),
            'customer_id'    => (int)$_POST['customer_id'], // Refactored
            'project_id'     => (int)$_POST['project_id'],
            'status'         => sanitize_text_field($_POST['status']),
            'issue_date'     => sanitize_text_field($_POST['issue_date']),
            'due_date'       => sanitize_text_field($_POST['due_date']),
            'title'          => 'Invoice ' . sanitize_text_field($_POST['invoice_number']),
            'amount'         => $total,
            'tax_amount'     => $tax,
            'line_items'     => json_encode($items),
            'currency'       => 'USD'
        ]);
        
        wp_redirect(admin_url('admin.php?page=studiofy-invoices'));
        exit;
    }
}
