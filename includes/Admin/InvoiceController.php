<?php
/**
 * Invoice Controller
 * @package Studiofy\Admin
 * @version 2.2.28
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

        // Allow sorting by columns
        $allowed_sort = ['invoice_number', 'title', 'amount', 'status', 'created_at'];
        if (!in_array($orderby, $allowed_sort)) $orderby = 'created_at';
        if (!in_array($order, ['ASC', 'DESC'])) $order = 'DESC';

        $sql = "SELECT i.*, c.first_name, c.last_name 
                FROM {$wpdb->prefix}studiofy_invoices i 
                LEFT JOIN {$wpdb->prefix}studiofy_customers c ON i.customer_id = c.id 
                ORDER BY i.$orderby $order";
        $rows = $wpdb->get_results($sql);
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_invoices");

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">Invoices</h1>';
        echo '<a href="?page=studiofy-invoices&action=create" class="page-title-action">New Invoice</a>';
        echo '<hr class="wp-header-end">';
        
        if ($count == 0) {
            echo '<div class="studiofy-empty-card">';
            echo '<div class="empty-icon dashicons dashicons-media-spreadsheet"></div>';
            echo '<h2>No invoices yet</h2>';
            echo '<p>Create your first invoice to start billing clients. Add line items, calculate totals, and generate professional PDFs.</p>';
            echo '<a href="?page=studiofy-invoices&action=create" class="button button-primary button-large">Create Invoice</a>';
            echo '</div>';
        } elseif (empty($rows)) {
            echo '<p>No invoices found matching your criteria.</p>';
        } else {
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
            echo '</tbody></table>';
        }
        echo '</div>';
    }

    private function render_builder(): void {
        global $wpdb;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $inv = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_invoices WHERE id = %d", $id)) : null;
        
        $customers = $wpdb->get_results("SELECT id, first_name, last_name FROM {$wpdb->prefix}studiofy_customers");
        $projects = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}studiofy_projects");
        $inv_num = $inv ? $inv->invoice_number : 'INV-' . strtoupper(uniqid());
        
        $line_items = [];
        if ($inv && !empty($inv->line_items)) {
            $decoded = json_decode($inv->line_items, true);
            if (is_array($decoded)) {
                $line_items = $decoded;
            }
        }
        
        $tax_amount = $inv ? (float)$inv->tax_amount : 0;
        $subtotal = $inv ? (float)$inv->amount - $tax_amount : 0;
        $tax_rate = ($subtotal > 0) ? ($tax_amount / $subtotal) * 100 : 0;
        
        require_once STUDIOFY_PATH . 'templates/admin/invoice-builder.php';
    }

    public function handle_save(): void {
        check_admin_referer('save_invoice', 'studiofy_nonce');
        global $wpdb;
        
        $items = $_POST['items'] ?? [];
        $subtotal = 0;
        if (is_array($items)) {
            foreach($items as $i) {
                $qty = isset($i['qty']) ? (float)$i['qty'] : 0;
                $rate = isset($i['rate']) ? (float)$i['rate'] : 0;
                $subtotal += ($qty * $rate);
            }
        }
        
        $tax_rate = isset($_POST['tax_rate']) ? (float)$_POST['tax_rate'] : 0;
        $tax_amt = $subtotal * ($tax_rate / 100);
        $total = $subtotal + $tax_amt;

        $data = [
            'invoice_number' => sanitize_text_field($_POST['invoice_number'] ?? ''),
            'customer_id' => (int)($_POST['customer_id'] ?? 0),
            'project_id' => (int)($_POST['project_id'] ?? 0),
            'status' => sanitize_text_field($_POST['status'] ?? 'Draft'),
            'issue_date' => sanitize_text_field($_POST['issue_date'] ?? ''),
            'due_date' => sanitize_text_field($_POST['due_date'] ?? ''),
            'title' => 'Invoice ' . sanitize_text_field($_POST['invoice_number'] ?? ''),
            'amount' => $total,
            'tax_amount' => $tax_amt,
            'line_items' => json_encode($items),
        ];

        if(!empty($_POST['id'])) {
            $wpdb->update($wpdb->prefix.'studiofy_invoices', $data, ['id'=>(int)$_POST['id']]);
        } else {
            $wpdb->insert($wpdb->prefix.'studiofy_invoices', $data);
        }
        
        wp_redirect(admin_url('admin.php?page=studiofy-invoices')); 
        exit;
    }

    public function handle_print(): void {
        check_admin_referer('print_invoice_'.$_GET['id']);
        global $wpdb;
        $inv = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_invoices WHERE id=%d", $_GET['id']));
        
        echo "<html><head><title>Invoice {$inv->invoice_number}</title><style>body{font-family:sans-serif; padding:40px;} table{width:100%; border-collapse:collapse;} th,td{border:1px solid #ccc; padding:8px;} .header{text-align:center; margin-bottom:40px;}</style></head><body>";
        echo "<div class='header'><h1>INVOICE</h1><h2>#{$inv->invoice_number}</h2></div>";
        echo "<p><strong>Date:</strong> {$inv->issue_date}<br><strong>Due:</strong> {$inv->due_date}</p>";
        echo "<table><thead><tr><th>Description</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead><tbody>";
        
        $items = (!empty($inv->line_items)) ? json_decode($inv->line_items, true) : [];
        if(is_array($items)) {
            foreach($items as $i) {
                $qty = isset($i['qty']) ? (float)$i['qty'] : 0;
                $rate = isset($i['rate']) ? (float)$i['rate'] : 0;
                $amt = number_format($qty * $rate, 2); // Calculated safely
                echo "<tr><td>".esc_html($i['desc'] ?? '')."</td><td>".esc_html($qty)."</td><td>".esc_html($rate)."</td><td>$$amt</td></tr>";
            }
        }
        echo "</tbody></table>";
        
        // FIX: Cast to float for number_format
        $total = (float)$inv->amount;
        echo "<h3 style='text-align:right'>Total: $".number_format($total, 2)."</h3>";
        
        echo "<script>window.print();</script></body></html>";
        exit;
    }
}
