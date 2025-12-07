<?php
/**
 * Invoice Controller
 * @package Studiofy\Admin
 * @version 2.2.47
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

    public function render_page(): void {
        $action = $_GET['action'] ?? 'list';
        if ($action === 'create' || $action === 'edit') {
            $this->render_builder();
        } elseif ($action === 'items' || $action === 'edit_item') {
            $this->render_items_page();
        } else {
            $this->render_list();
        }
    }

    private function render_list(): void {
        global $wpdb;
        $orderby = $_GET['orderby'] ?? 'created_at';
        $order = strtoupper($_GET['order'] ?? 'DESC');
        $sql = "SELECT i.*, c.first_name, c.last_name FROM {$wpdb->prefix}studiofy_invoices i LEFT JOIN {$wpdb->prefix}studiofy_customers c ON i.customer_id = c.id ORDER BY i.$orderby $order";
        $rows = $wpdb->get_results($sql);
        
        echo '<div class="wrap"><h1 class="wp-heading-inline">Invoices</h1><a href="?page=studiofy-invoices&action=create" class="page-title-action">New Invoice</a><a href="?page=studiofy-invoices&action=items" class="page-title-action">Manage Items</a><hr class="wp-header-end">';
        if (empty($rows)) {
            echo '<div class="studiofy-empty-card"><div class="empty-icon dashicons dashicons-media-spreadsheet"></div><h2>No invoices yet</h2><p>Create your first invoice.</p><a href="?page=studiofy-invoices&action=create" class="button button-primary button-large">Create Invoice</a></div>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Invoice ID</th><th>Title</th><th>Customer</th><th>Amount</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
            foreach ($rows as $r) {
                $customer = $r->first_name ? esc_html($r->first_name . ' ' . $r->last_name) : 'Unknown';
                $edit_url = "?page=studiofy-invoices&action=edit&id={$r->id}";
                $print_url = wp_nonce_url(admin_url("admin-post.php?action=studiofy_print_invoice&id={$r->id}"), 'print_invoice_'.$r->id);
                echo "<tr><td><a href='$edit_url'><strong>" . esc_html($r->invoice_number) . "</strong></a></td><td>" . esc_html($r->title) . "</td><td>$customer</td><td>$" . number_format((float)$r->amount, 2) . "</td><td><span class='studiofy-badge " . esc_attr(strtolower($r->status)) . "'>" . esc_html($r->status) . "</span></td><td><a href='" . esc_url($r->payment_link) . "' target='_blank' class='button button-small'>Pay</a> <a href='$edit_url' class='button button-small'>Modify</a> <a href='$print_url' target='_blank' class='button button-small'>Print</a></td></tr>";
            }
            echo '</tbody></table>';
        }
        echo '</div>';
    }

    private function render_items_page(): void {
        global $wpdb;
        $items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}studiofy_items ORDER BY title ASC");
        $edit_id = isset($_GET['id']) && $_GET['action'] === 'edit_item' ? (int)$_GET['id'] : 0;
        $edit_data = null;
        if ($edit_id) { $edit_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_items WHERE id = %d", $edit_id)); }
        $form_title = $edit_data ? 'Edit Item' : 'Add New Item';
        $btn_text = $edit_data ? 'Update Item' : 'Add Item';

        echo '<div class="wrap"><h1 class="wp-heading-inline">Item Library</h1><a href="?page=studiofy-invoices" class="page-title-action">Back to Invoices</a>';
        if($edit_data) echo '<a href="?page=studiofy-invoices&action=items" class="page-title-action">Cancel Edit</a>';
        echo '<hr class="wp-header-end"><div class="studiofy-panel" style="margin-bottom:20px;"><h3>' . $form_title . '</h3><form method="post" action="'.admin_url('admin-post.php').'"><input type="hidden" name="action" value="studiofy_save_item">';
        if($edit_data) echo '<input type="hidden" name="id" value="'.$edit_data->id.'">';
        wp_nonce_field('save_item', 'studiofy_nonce');
        echo '<div class="studiofy-form-row"><div class="studiofy-col"><label>Item Name</label><input type="text" name="title" required class="widefat" value="'.esc_attr($edit_data->title ?? '').'"></div><div class="studiofy-col"><label>Rate ($)</label><input type="number" step="0.01" name="rate" required class="widefat" value="'.esc_attr($edit_data->rate ?? '').'"></div><div class="studiofy-col"><label>Type</label><select name="rate_type" class="widefat"><option value="Fixed" '.selected($edit_data->rate_type ?? '', 'Fixed', false).'>Fixed</option><option value="Hourly" '.selected($edit_data->rate_type ?? '', 'Hourly', false).'>Hourly</option></select></div></div><div class="studiofy-form-row"><div class="studiofy-col"><label>Default Qty</label><input type="number" name="default_qty" value="'.esc_attr($edit_data->default_qty ?? '1').'" class="widefat"></div><div class="studiofy-col"><label>Tax (%)</label><input type="number" step="0.01" name="tax_rate" value="'.esc_attr($edit_data->tax_rate ?? '0.00').'" class="widefat"></div><div class="studiofy-col"><label>Desc</label><input type="text" name="description" value="'.esc_attr($edit_data->description ?? '').'" class="widefat"></div></div><p><button type="submit" class="button button-primary">' . $btn_text . '</button></p></form></div>';
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Name</th><th>Desc</th><th>Rate</th><th>Qty</th><th>Tax</th><th>Actions</th></tr></thead><tbody>';
        if (empty($items)) { echo '<tr><td colspan="6">No items.</td></tr>'; } 
        else { foreach ($items as $item) { $edit_link = "?page=studiofy-invoices&action=edit_item&id={$item->id}"; $del_url = wp_nonce_url(admin_url("admin-post.php?action=studiofy_delete_item&id={$item->id}"), 'delete_item_'.$item->id); echo "<tr><td><strong><a href='$edit_link'>".esc_html($item->title)."</a></strong></td><td>".esc_html($item->description)."</td><td>$".number_format((float)$item->rate, 2)."</td><td>".esc_html($item->default_qty)."</td><td>".esc_html($item->tax_rate)."%</td><td><a href='$edit_link' class='button button-small'>Edit</a> <a href='$del_url' class='button button-small' onclick='return confirm(\"Delete?\")' style='color:#b32d2e;'>Delete</a></td></tr>"; } } 
        echo '</tbody></table></div>';
    }

    private function render_builder(): void {
        global $wpdb;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $inv = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_invoices WHERE id = %d", $id)) : null;
        
        // FIX: Ensure $inv object structure for new invoices to avoid undefined property warnings
        if (!$inv) {
            $inv = new \stdClass();
            $inv->id = 0; 
            $inv->invoice_number = 'INV-' . strtoupper(uniqid());
            $inv->status = 'Draft';
            $inv->issue_date = date('Y-m-d');
            $inv->due_date = date('Y-m-d', strtotime('+30 days'));
            $inv->amount = 0.00;
            $inv->tax_amount = 0.00;
            $inv->line_items = '[]';
            $inv->customer_id = 0;
            $inv->project_id = 0;
            $inv->currency = 'USD';
            $inv->title = '';
        }
        
        // FIX: Initialize Customer Object completely to prevent "property on null"
        $customer = new \stdClass();
        $customer->name = 'Walk-in Client';
        $customer->company = '';
        $customer->address = '';
        $customer->email = '';
        $customer->phone = '';
        
        if (!empty($inv->customer_id)) {
            $enc = new Encryption();
            $cust_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_customers WHERE id = %d", $inv->customer_id));
            if ($cust_row) {
                $customer->name = $cust_row->first_name . ' ' . $cust_row->last_name;
                $customer->company = $cust_row->company;
                $customer->address = $enc->decrypt($cust_row->address);
                $customer->email = $cust_row->email;
                $customer->phone = $enc->decrypt($cust_row->phone);
            }
        }
        
        // Prepare Data for Template Preview
        // FIX: Retrieve Business Name
        $options = (array)get_option('studiofy_branding', []);
        $business_name = !empty($options['business_name']) ? (string)$options['business_name'] : 'Photography Studio';
        
        // FIX: Hydrate Formatted Dates and Payment Info
        $inv->issue_date_formatted = date('m/d/Y', strtotime($inv->issue_date));
        $inv->due_date_formatted = date('m/d/Y', strtotime($inv->due_date));
        $inv->payable_to = "Payable to " . esc_html($business_name) . " Upon Receipt";
        
        // FIX: Decode Line Items
        $inv->line_items_data = json_decode($inv->line_items, true) ?: [];
        
        // FIX: Calculate Subtotal
        $inv->subtotal = 0;
        foreach($inv->line_items_data as $item) {
            $inv->subtotal += ((float)$item['qty'] * (float)$item['rate']);
        }

        $customers = $wpdb->get_results("SELECT id, first_name, last_name FROM {$wpdb->prefix}studiofy_customers");
        $projects = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}studiofy_projects");
        $saved_items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}studiofy_items ORDER BY title ASC"); 
        
        // Pass to template
        $invoice = $inv;
        // Also ensure legacy variables for the form part are set
        $line_items = $inv->line_items_data;
        $tax_rate = ($inv->subtotal > 0) ? ((float)$inv->tax_amount / $inv->subtotal) * 100 : 6.00;

        require_once STUDIOFY_PATH . 'templates/admin/invoice-builder.php';
    }

    public function handle_save(): void {
        check_admin_referer('save_invoice', 'studiofy_nonce');
        global $wpdb;
        
        $items = $_POST['items'] ?? [];
        $subtotal = 0;
        $cleaned_items = [];
        if (is_array($items)) {
            foreach($items as $i) {
                $qty = isset($i['qty']) ? (float)$i['qty'] : 0;
                $rate = isset($i['rate']) ? (float)$i['rate'] : 0;
                $desc = isset($i['desc']) ? sanitize_text_field($i['desc']) : '';
                $subtotal += ($qty * $rate);
                $cleaned_items[] = ['desc'=>$desc, 'qty'=>$qty, 'rate'=>$rate];
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
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'amount' => $total,
            'tax_amount' => $tax_amt,
            'line_items' => json_encode($cleaned_items),
            'currency' => sanitize_text_field($_POST['currency'] ?? 'USD')
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
        $data = ['title'=>sanitize_text_field($_POST['title']),'description'=>sanitize_text_field($_POST['description']),'rate'=>(float)$_POST['rate'],'rate_type'=>sanitize_text_field($_POST['rate_type']),'default_qty'=>(int)$_POST['default_qty'],'tax_rate'=>(float)$_POST['tax_rate']];
        if(!empty($_POST['id'])) $wpdb->update($wpdb->prefix.'studiofy_items', $data, ['id'=>(int)$_POST['id']]);
        else $wpdb->insert($wpdb->prefix.'studiofy_items', $data);
        wp_redirect(admin_url('admin.php?page=studiofy-invoices&action=items')); exit;
    }

    public function handle_delete_item(): void {
        check_admin_referer('delete_item_'.$_GET['id']);
        global $wpdb;
        $wpdb->delete($wpdb->prefix.'studiofy_items', ['id'=>(int)$_GET['id']]);
        wp_redirect(admin_url('admin.php?page=studiofy-invoices&action=items')); exit;
    }

    public function handle_print(): void {
        check_admin_referer('print_invoice_'.$_GET['id']);
        // (This remains as is, matching v2.2.42 PDF logic)
        global $wpdb;
        $id = (int)$_GET['id'];
        $invoice = $wpdb->get_row($wpdb->prepare("SELECT i.*, c.first_name, c.last_name, c.email, c.phone, c.company, c.address FROM {$wpdb->prefix}studiofy_invoices i LEFT JOIN {$wpdb->prefix}studiofy_customers c ON i.customer_id = c.id WHERE i.id = %d", $id));
        if (!$invoice) wp_die('Invoice not found.');
        
        $branding = (array) get_option('studiofy_branding', []);
        $business_name = !empty($branding['business_name']) ? (string)$branding['business_name'] : 'Photography Studio';
        
        $enc = new Encryption();
        $customer = new \stdClass();
        $customer->name = esc_html($invoice->first_name . ' ' . $invoice->last_name);
        $customer->company = esc_html($invoice->company);
        $customer->address = esc_html($enc->decrypt($invoice->address));
        
        $invoice->issue_date_formatted = date('m/d/Y', strtotime($invoice->issue_date));
        $invoice->due_date_formatted = date('m/d/Y', strtotime($invoice->due_date));
        $invoice->payable_to = "Payable to " . esc_html($business_name) . " Upon Receipt";
        $invoice->line_items_data = json_decode($invoice->line_items, true) ?: [];
        $invoice->subtotal = 0;
        foreach($invoice->line_items_data as $item) { $invoice->subtotal += ((float)$item['qty'] * (float)$item['rate']); }

        ob_start();
        include STUDIOFY_PATH . 'templates/admin/invoice-template.php';
        $html = ob_get_clean();
        
        require_once STUDIOFY_PATH . 'vendor/autoload.php';
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('Invoice-' . $invoice->invoice_number . '.pdf', ['Attachment' => false]);
        exit;
    }
}
