<?php
/**
 * Invoice Controller
 * @package Studiofy\Admin
 * @version 2.1.5
 */

declare(strict_types=1);

namespace Studiofy\Admin;

use Studiofy\Utils\TableHelper;

class InvoiceController {
    use TableHelper;

    public function init(): void {
        add_action('admin_post_studiofy_save_invoice', [$this, 'handle_save']);
        add_action('admin_post_studiofy_print_invoice', [$this, 'handle_print']);
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
        $orderby = $_GET['orderby'] ?? 'created_at';
        $order = strtoupper($_GET['order'] ?? 'DESC');

        $sql = "SELECT i.*, c.first_name, c.last_name 
                FROM {$wpdb->prefix}studiofy_invoices i 
                LEFT JOIN {$wpdb->prefix}studiofy_customers c ON i.customer_id = c.id 
                ORDER BY i.$orderby $order";
        $rows = $wpdb->get_results($sql);

        echo '<div class="wrap"><h1>Invoices <a href="?page=studiofy-invoices&action=create" class="page-title-action">New Invoice</a></h1>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>
            <th class="manage-column sortable">'.$this->sort_link('Invoice ID', 'invoice_number').'</th>
            <th class="manage-column sortable">'.$this->sort_link('Title', 'title').'</th>
            <th class="manage-column">Customer</th>
            <th class="manage-column sortable">'.$this->sort_link('Amount', 'amount').'</th>
            <th class="manage-column sortable">'.$this->sort_link('Status', 'status').'</th>
            <th class="manage-column">Actions</th>
        </tr></thead><tbody>';
        
        foreach ($rows as $r) {
            $customer = $r->first_name ? esc_html($r->first_name . ' ' . $r->last_name) : 'Unknown';
            $edit_url = "?page=studiofy-invoices&action=edit&id={$r->id}";
            $print_url = wp_nonce_url(admin_url("admin-post.php?action=studiofy_print_invoice&id={$r->id}"), 'print_invoice_'.$r->id);
            
            echo "<tr>
                <td><a href='$edit_url'><strong>" . esc_html($r->invoice_number) . "</strong></a></td>
                <td>" . esc_html($r->title) . "</td>
                <td>$customer</td>
                <td>$" . esc_html($r->amount) . "</td>
                <td><span class='studiofy-badge " . esc_attr(strtolower($r->status)) . "'>" . esc_html($r->status) . "</span></td>
                <td>
                    <a href='" . esc_url($r->payment_link) . "' target='_blank' class='button button-small'>Pay</a>
                    <a href='$edit_url' class='button button-small'>Modify</a>
                    <a href='$print_url' target='_blank' class='button button-small'>Print</a>
                </td>
            </tr>";
        }
        echo '</tbody></table></div>';
    }

    private function render_builder(): void {
        global $wpdb;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $inv = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_invoices WHERE id = %d", $id)) : null;
        
        $customers = $wpdb->get_results("SELECT id, first_name, last_name FROM {$wpdb->prefix}studiofy_customers");
        $projects = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}studiofy_projects");
        $inv_num = $inv ? $inv->invoice_number : 'INV-' . strtoupper(uniqid());
        
        // Pass data to template
        $line_items = $inv ? json_decode($inv->line_items, true) : [];
        $tax_rate = $inv ? $inv->tax_rate : 0; // New column or derived
        // Note: DB schema v2.5 used tax_amount, we need to adapt JS to save tax_amount but UI uses Rate. 
        // For simplicity, we calculate rate if not stored: (tax_amount / subtotal) * 100
        
        require_once STUDIOFY_PATH . 'templates/admin/invoice-builder.php';
    }

    public function handle_save(): void {
        check_admin_referer('save_invoice', 'studiofy_nonce');
        global $wpdb;
        
        $items = $_POST['items'] ?? [];
        $subtotal = 0;
        foreach($items as $i) $subtotal += ((float)$i['qty'] * (float)$i['rate']);
        
        $tax_rate = (float)$_POST['tax_rate']; // Percentage
        $tax_amt = $subtotal * ($tax_rate / 100);
        $total = $subtotal + $tax_amt;

        $data = [
            'invoice_number' => sanitize_text_field($_POST['invoice_number']),
            'customer_id' => (int)$_POST['customer_id'],
            'project_id' => (int)$_POST['project_id'],
            'status' => sanitize_text_field($_POST['status']),
            'issue_date' => sanitize_text_field($_POST['issue_date']),
            'due_date' => sanitize_text_field($_POST['due_date']),
            'title' => 'Invoice ' . sanitize_text_field($_POST['invoice_number']),
            'amount' => $total,
            'tax_amount' => $tax_amt, // Saved as amount
            'line_items' => json_encode($items),
        ];

        if(!empty($_POST['id'])) $wpdb->update($wpdb->prefix.'studiofy_invoices', $data, ['id'=>(int)$_POST['id']]);
        else $wpdb->insert($wpdb->prefix.'studiofy_invoices', $data);
        
        wp_redirect(admin_url('admin.php?page=studiofy-invoices')); exit;
    }

    public function handle_print(): void {
        // Simple Print View
        check_admin_referer('print_invoice_'.$_GET['id']);
        global $wpdb;
        $inv = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_invoices WHERE id=%d", $_GET['id']));
        
        echo "<html><head><title>Invoice {$inv->invoice_number}</title><style>body{font-family:sans-serif; padding:40px;} table{width:100%; border-collapse:collapse;} th,td{border:1px solid #ccc; padding:8px;} .header{text-align:center; margin-bottom:40px;}</style></head><body>";
        echo "<div class='header'><h1>INVOICE</h1><h2>#{$inv->invoice_number}</h2></div>";
        echo "<p><strong>Date:</strong> {$inv->issue_date}<br><strong>Due:</strong> {$inv->due_date}</p>";
        echo "<table><thead><tr><th>Description</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead><tbody>";
        $items = json_decode($inv->line_items, true);
        foreach($items as $i) {
            $amt = number_format($i['qty']*$i['rate'], 2);
            echo "<tr><td>{$i['desc']}</td><td>{$i['qty']}</td><td>{$i['rate']}</td><td>$$amt</td></tr>";
        }
        echo "</tbody></table>";
        echo "<h3 style='text-align:right'>Total: $".number_format($inv->amount, 2)."</h3>";
        echo "<script>window.print();</script></body></html>";
        exit;
    }
}
