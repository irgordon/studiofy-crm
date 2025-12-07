<?php
/**
 * Invoice Controller
 * @package Studiofy\Admin
 * @version 2.2.49
 */

declare(strict_types=1);

namespace Studiofy\Admin;

use Studiofy\Utils\TableHelper;
use Studiofy\Security\Encryption;

class InvoiceController {
    use TableHelper;

    public function init(): void {
        add_action('admin_post_studiofy_save_invoice', [$this, 'handle_save']);
        add_action('admin_post_studiofy_save_item', [$this, 'handle_save_item']);
        add_action('admin_post_studiofy_delete_item', [$this, 'handle_delete_item']);
        add_action('admin_post_studiofy_print_invoice', [$this, 'handle_print']);
    }

    // ... (render_page, render_list, render_items_page, render_builder, handle_delete_item, handle_print same as v2.2.45) ...
    public function render_page(): void { $action = $_GET['action'] ?? 'list'; if ($action === 'create' || $action === 'edit') { $this->render_builder(); } elseif ($action === 'items' || $action === 'edit_item') { $this->render_items_page(); } else { $this->render_list(); } }
    private function render_list(): void { global $wpdb; $orderby = $_GET['orderby'] ?? 'created_at'; $order = strtoupper($_GET['order'] ?? 'DESC'); $sql = "SELECT i.*, c.first_name, c.last_name FROM {$wpdb->prefix}studiofy_invoices i LEFT JOIN {$wpdb->prefix}studiofy_customers c ON i.customer_id = c.id ORDER BY i.$orderby $order"; $rows = $wpdb->get_results($sql); echo '<div class="wrap"><h1 class="wp-heading-inline">Invoices</h1><a href="?page=studiofy-invoices&action=create" class="page-title-action">New Invoice</a><a href="?page=studiofy-invoices&action=items" class="page-title-action">Manage Items</a><hr class="wp-header-end">'; if (empty($rows)) { echo '<div class="studiofy-empty-card"><div class="empty-icon dashicons dashicons-media-spreadsheet"></div><h2>No invoices yet</h2><p>Create your first invoice.</p><a href="?page=studiofy-invoices&action=create" class="button button-primary button-large">Create Invoice</a></div>'; } else { echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Invoice ID</th><th>Title</th><th>Customer</th><th>Amount</th><th>Status</th><th>Actions</th></tr></thead><tbody>'; foreach ($rows as $r) { $customer = $r->first_name ? esc_html($r->first_name . ' ' . $r->last_name) : 'Unknown'; $edit_url = "?page=studiofy-invoices&action=edit&id={$r->id}"; $print_url = wp_nonce_url(admin_url("admin-post.php?action=studiofy_print_invoice&id={$r->id}"), 'print_invoice_'.$r->id); echo "<tr><td><a href='$edit_url'><strong>" . esc_html($r->invoice_number) . "</strong></a></td><td>" . esc_html($r->title) . "</td><td>$customer</td><td>$" . number_format((float)$r->amount, 2) . "</td><td><span class='studiofy-badge " . esc_attr(strtolower($r->status)) . "'>" . esc_html($r->status) . "</span></td><td><a href='" . esc_url($r->payment_link) . "' target='_blank' class='button button-small'>Pay</a> <a href='$edit_url' class='button button-small'>Modify</a> <a href='$print_url' target='_blank' class='button button-small'>Print</a></td></tr>"; } echo '</tbody></table>'; } echo '</div>'; }
    private function render_items_page(): void { global $wpdb; $items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}studiofy_items ORDER BY title ASC"); $edit_id = isset($_GET['id']) && $_GET['action'] === 'edit_item' ? (int)$_GET['id'] : 0; $edit_data = null; if ($edit_id) { $edit_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_items WHERE id = %d", $edit_id)); } $form_title = $edit_data ? 'Edit Item' : 'Add New Item'; $btn_text = $edit_data ? 'Update Item' : 'Add Item'; echo '<div class="wrap"><h1 class="wp-heading-inline">Item Library</h1><a href="?page=studiofy-invoices" class="page-title-action">Back to Invoices</a>'; if($edit_data) echo '<a href="?page=studiofy-invoices&action=items" class="page-title-action">Cancel Edit</a>'; echo '<hr class="wp-header-end"><div class="studiofy-panel" style="margin-bottom:20px;"><h3>' . $form_title . '</h3><form method="post" action="'.admin_url('admin-post.php').'"><input type="hidden" name="action" value="studiofy_save_item">'; if($edit_data) echo '<input type="hidden" name="id" value="'.$edit_data->id.'">'; wp_nonce_field('save_item', 'studiofy_nonce'); echo '<div class="studiofy-form-row"><div class="studiofy-col"><label>Item Name</label><input type="text" name="title" required class="widefat" value="'.esc_attr($edit_data->title ?? '').'"></div><div class="studiofy-col"><label>Rate ($)</label><input type="number" step="0.01" name="rate" required class="widefat" value="'.esc_attr($edit_data->rate ?? '').'"></div><div class="studiofy-col"><label>Type</label><select name="rate_type" class="widefat"><option value="Fixed" '.selected($edit_data->rate_type ?? '', 'Fixed', false).'>Fixed</option><option value="Hourly" '.selected($edit_data->rate_type ?? '', 'Hourly', false).'>Hourly</option></select></div></div><div class="studiofy-form-row"><div class="studiofy-col"><label>Default Qty</label><input type="number" name="default_qty" value="'.esc_attr($edit_data->default_qty ?? '1').'" class="widefat"></div><div class="studiofy-col"><label>Tax (%)</label><input type="number" step="0.01" name="tax_rate" value="'.esc_attr($edit_data->tax_rate ?? '0.00').'" class="widefat"></div><div class="studiofy-col"><label>Desc</label><input type="text" name="description" value="'.esc_attr($edit_data->description ?? '').'" class="widefat"></div></div><p><button type="submit" class="button button-primary">' . $btn_text . '</button></p></form></div><table class="wp-list-table widefat fixed striped"><thead><tr><th>Name</th><th>Desc</th><th>Rate</th><th>Qty</th><th>Tax</th><th>Actions</th></tr></thead><tbody>'; if (empty($items)) { echo '<tr><td colspan="6">No items.</td></tr>'; } else { foreach ($items as $item) { $edit_link = "?page=studiofy-invoices&action=edit_item&id={$item->id}"; $del_url = wp_nonce_url(admin_url("admin-post.php?action=studiofy_delete_item&id={$item->id}"), 'delete_item_'.$item->id); echo "<tr><td><strong><a href='$edit_link'>".esc_html($item->title)."</a></strong></td><td>".esc_html($item->description)."</td><td>$".number_format((float)$item->rate, 2)."</td><td>".esc_html($item->default_qty)."</td><td>".esc_html($item->tax_rate)."%</td><td><a href='$edit_link' class='button button-small'>Edit</a> <a href='$del_url' class='button button-small' onclick='return confirm(\"Delete?\")' style='color:#b32d2e;'>Delete</a></td></tr>"; } } echo '</tbody></table></div>'; }
    private function render_builder(): void { global $wpdb; $id = isset($_GET['id']) ? (int)$_GET['id'] : 0; $inv = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_invoices WHERE id = %d", $id)) : null; if (!$inv) { $inv = new \stdClass(); $inv->id = 0; $inv->invoice_number = 'INV-' . strtoupper(uniqid()); $inv->status = 'Draft'; $inv->issue_date = date('Y-m-d'); $inv->due_date = date('Y-m-d', strtotime('+30 days')); $inv->amount = 0.00; $inv->tax_amount = 0.00; $inv->line_items = '[]'; $inv->customer_id = 0; $inv->project_id = 0; $inv->currency = 'USD'; $inv->title = ''; } $customer = new \stdClass(); $customer->name = ''; $customer->company = ''; $customer->address = ''; $customer->email = ''; $customer->phone = ''; if (!empty($inv->customer_id)) { $enc = new Encryption(); $cust_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_customers WHERE id = %d", $inv->customer_id)); if ($cust_row) { $customer->name = $cust_row->first_name . ' ' . $cust_row->last_name; $customer->company = $cust_row->company; $customer->address = $enc->decrypt($cust_row->address); $customer->email = $cust_row->email; $customer->phone = $enc->decrypt($cust_row->phone); } } $customers = $wpdb->get_results("SELECT id, first_name, last_name FROM {$wpdb->prefix}studiofy_customers"); $projects = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}studiofy_projects"); $saved_items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}studiofy_items ORDER BY title ASC"); $inv_num = $inv->invoice_number; $line_items = []; if (!empty($inv->line_items)) { $decoded = json_decode($inv->line_items, true); if (is_array($decoded)) $line_items = $decoded; } $tax_amount = (float)$inv->tax_amount; $subtotal = (float)$inv->amount - $tax_amount; $tax_rate = ($subtotal > 0) ? ($tax_amount / $subtotal) * 100 : 6.00; $invoice = $inv; require_once STUDIOFY_PATH . 'templates/admin/invoice-builder.php'; }
    public function handle_delete_item(): void { check_admin_referer('delete_item_'.$_GET['id']); global $wpdb; $wpdb->delete($wpdb->prefix.'studiofy_items', ['id'=>(int)$_GET['id']]); wp_redirect(admin_url('admin.php?page=studiofy-invoices&action=items')); exit; }
    public function handle_print(): void { check_admin_referer('print_invoice_'.$_GET['id']); global $wpdb; $inv = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_invoices WHERE id=%d", $_GET['id'])); echo "<html><head><title>Invoice {$inv->invoice_number}</title><style>body{font-family:sans-serif; padding:40px;} table{width:100%; border-collapse:collapse;} th,td{border:1px solid #ccc; padding:8px;} .header{text-align:center; margin-bottom:40px;}</style></head><body>"; echo "<div class='header'><h1>INVOICE</h1><h2>#{$inv->invoice_number}</h2></div>"; echo "<p><strong>Date:</strong> {$inv->issue_date}<br><strong>Due:</strong> {$inv->due_date}</p>"; echo "<table><thead><tr><th>Description</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead><tbody>"; $items = (!empty($inv->line_items)) ? json_decode($inv->line_items, true) : []; if(is_array($items)) { foreach($items as $i) { $amt = number_format((float)$i['qty'] * (float)$i['rate'], 2); echo "<tr><td>".esc_html($i['desc'] ?? '')."</td><td>".esc_html($i['qty'])."</td><td>".esc_html($i['rate'])."</td><td>$$amt</td></tr>"; } } echo "</tbody></table><h3 style='text-align:right'>Total: $".number_format((float)$inv->amount, 2)."</h3><script>window.print();</script></body></html>"; exit; }

    // FIX: Strict casting for all inputs to prevent ltrim(null) warnings
    public function handle_save(): void {
        check_admin_referer('save_invoice', 'studiofy_nonce');
        global $wpdb;
        
        $items = $_POST['items'] ?? [];
        $subtotal = 0;
        $cleaned_items = [];
        if (is_array($items)) {
            foreach($items as $i) {
                // Cast to float safely
                $qty = isset($i['qty']) ? (float)$i['qty'] : 0;
                $rate = isset($i['rate']) ? (float)$i['rate'] : 0;
                // Strict cast string to prevent deprecation
                $desc = isset($i['desc']) ? (string)$i['desc'] : '';
                $subtotal += ($qty * $rate);
                $cleaned_items[] = ['desc'=>sanitize_text_field($desc), 'qty'=>$qty, 'rate'=>$rate];
            }
        }
        
        $tax_rate = isset($_POST['tax_rate']) ? (float)$_POST['tax_rate'] : 0;
        $tax_amt = $subtotal * ($tax_rate / 100);
        $total = $subtotal + $tax_amt;

        // Fix: Cast all potentially null POSTs to strings before sanitization
        $inv_num = isset($_POST['invoice_number']) ? (string)$_POST['invoice_number'] : '';
        $title = isset($_POST['title']) ? (string)$_POST['title'] : '';
        $status = isset($_POST['status']) ? (string)$_POST['status'] : 'Draft';
        $issue_date = isset($_POST['issue_date']) ? (string)$_POST['issue_date'] : '';
        $due_date = isset($_POST['due_date']) ? (string)$_POST['due_date'] : '';
        $currency = isset($_POST['currency']) ? (string)$_POST['currency'] : 'USD';

        $data = [
            'invoice_number' => sanitize_text_field($inv_num),
            'customer_id' => (int)($_POST['customer_id'] ?? 0),
            'project_id' => (int)($_POST['project_id'] ?? 0),
            'status' => sanitize_text_field($status),
            'issue_date' => sanitize_text_field($issue_date),
            'due_date' => sanitize_text_field($due_date),
            'title' => sanitize_text_field($title),
            'amount' => $total,
            'tax_amount' => $tax_amt,
            'line_items' => json_encode($cleaned_items),
            'currency' => sanitize_text_field($currency)
        ];

        if(!empty($_POST['id'])) {
            $wpdb->update($wpdb->prefix.'studiofy_invoices', $data, ['id'=>(int)$_POST['id']]);
        } else {
            $wpdb->insert($wpdb->prefix.'studiofy_invoices', array_merge($data, ['created_at' => current_time('mysql')]));
        }
        wp_redirect(admin_url('admin.php?page=studiofy-invoices&msg=saved')); exit;
    }

    public function handle_save_item(): void {
        check_admin_referer('save_item', 'studiofy_nonce');
        global $wpdb;
        
        // Fix: Strict casting
        $title = isset($_POST['title']) ? (string)$_POST['title'] : '';
        $desc = isset($_POST['description']) ? (string)$_POST['description'] : '';
        $type = isset($_POST['rate_type']) ? (string)$_POST['rate_type'] : 'Fixed';

        $data = [
            'title' => sanitize_text_field($title),
            'description' => sanitize_text_field($desc),
            'rate' => (float)($_POST['rate'] ?? 0),
            'rate_type' => sanitize_text_field($type),
            'default_qty' => (int)($_POST['default_qty'] ?? 1),
            'tax_rate' => (float)($_POST['tax_rate'] ?? 0)
        ];

        if(!empty($_POST['id'])) $wpdb->update($wpdb->prefix.'studiofy_items', $data, ['id'=>(int)$_POST['id']]);
        else $wpdb->insert($wpdb->prefix.'studiofy_items', $data);
        
        wp_redirect(admin_url('admin.php?page=studiofy-invoices&action=items')); exit;
    }
}
