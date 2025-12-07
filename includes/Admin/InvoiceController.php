<?php
/**
 * Invoice Controller
 * @package Studiofy\Admin
 * @version 2.2.31
 */

declare(strict_types=1);

namespace Studiofy\Admin;

use Studiofy\Utils\TableHelper;

class InvoiceController {
    use TableHelper;

    public function init(): void {
        add_action('admin_post_studiofy_save_invoice', [$this, 'handle_save']);
        add_action('admin_post_studiofy_save_item', [$this, 'handle_save_item']); // New
        add_action('admin_post_studiofy_delete_item', [$this, 'handle_delete_item']); // New
        add_action('admin_post_studiofy_print_invoice', [$this, 'handle_print']);
    }

    public function render_page(): void {
        $action = $_GET['action'] ?? 'list';
        
        // Tab Navigation
        $tabs = [
            'list' => 'Invoices',
            'items' => 'Item Library'
        ];
        $current_tab = ($action === 'items') ? 'items' : 'list';
        
        if ($action === 'create' || $action === 'edit') {
            $this->render_builder();
        } elseif ($action === 'items') {
            $this->render_items_page();
        } else {
            $this->render_list();
        }
    }

    private function render_list(): void {
        global $wpdb;
        
        echo '<div class="wrap"><h1 class="wp-heading-inline">Invoices</h1>';
        echo '<a href="?page=studiofy-invoices&action=create" class="page-title-action">New Invoice</a>';
        echo '<a href="?page=studiofy-invoices&action=items" class="page-title-action">Manage Items</a>';
        echo '<hr class="wp-header-end">';

        $orderby = $_GET['orderby'] ?? 'created_at';
        $order = strtoupper($_GET['order'] ?? 'DESC');
        $sql = "SELECT i.*, c.first_name, c.last_name FROM {$wpdb->prefix}studiofy_invoices i LEFT JOIN {$wpdb->prefix}studiofy_customers c ON i.customer_id = c.id ORDER BY i.$orderby $order";
        $rows = $wpdb->get_results($sql);
        
        if (empty($rows)) {
            echo '<div class="studiofy-empty-card"><div class="empty-icon dashicons dashicons-media-spreadsheet"></div><h2>No invoices yet</h2><p>Create your first invoice to start billing clients.</p><a href="?page=studiofy-invoices&action=create" class="button button-primary button-large">Create Invoice</a></div>';
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
        
        echo '<div class="wrap"><h1 class="wp-heading-inline">Item Library</h1>';
        echo '<a href="?page=studiofy-invoices" class="page-title-action">Back to Invoices</a>';
        echo '<hr class="wp-header-end">';
        
        // Add Item Form
        echo '<div class="studiofy-panel" style="margin-bottom:20px;"><h3>Add New Item</h3>';
        echo '<form method="post" action="'.admin_url('admin-post.php').'">';
        echo '<input type="hidden" name="action" value="studiofy_save_item">';
        wp_nonce_field('save_item', 'studiofy_nonce');
        echo '<div class="studiofy-form-row">';
        echo '<div class="studiofy-col"><label for="i_title">Item Name</label><input type="text" name="title" id="i_title" required class="widefat" placeholder="e.g., Portrait Session"></div>';
        echo '<div class="studiofy-col"><label for="i_rate">Rate ($)</label><input type="number" step="0.01" name="rate" id="i_rate" required class="widefat" placeholder="0.00"></div>';
        echo '<div class="studiofy-col"><label for="i_type">Rate Type</label><select name="rate_type" id="i_type" class="widefat"><option value="Fixed">Fixed</option><option value="Hourly">Hourly</option><option value="Day">Day</option></select></div>';
        echo '</div>';
        echo '<div class="studiofy-form-row">';
        echo '<div class="studiofy-col"><label for="i_qty">Default Qty</label><input type="number" name="default_qty" id="i_qty" value="1" class="widefat"></div>';
        echo '<div class="studiofy-col"><label for="i_tax">Tax Rate (%)</label><input type="number" step="0.01" name="tax_rate" id="i_tax" value="0.00" class="widefat"></div>';
        echo '<div class="studiofy-col"><label for="i_desc">Description</label><input type="text" name="description" id="i_desc" class="widefat"></div>';
        echo '</div>';
        echo '<p><button type="submit" class="button button-primary">Add Item</button></p>';
        echo '</form></div>';

        // Items Table
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Item Name</th><th>Description</th><th>Rate</th><th>Default Qty</th><th>Tax %</th><th>Actions</th></tr></thead><tbody>';
        if (empty($items)) {
            echo '<tr><td colspan="6">No items in library. Add one above.</td></tr>';
        } else {
            foreach ($items as $item) {
                $del_url = wp_nonce_url(admin_url("admin-post.php?action=studiofy_delete_item&id={$item->id}"), 'delete_item_'.$item->id);
                echo "<tr>
                    <td><strong>".esc_html($item->title)."</strong></td>
                    <td>".esc_html($item->description)."</td>
                    <td>$".number_format((float)$item->rate, 2)." / ".esc_html($item->rate_type)."</td>
                    <td>".esc_html($item->default_qty)."</td>
                    <td>".esc_html($item->tax_rate)."%</td>
                    <td><a href='$del_url' class='button button-small button-link-delete' onclick='return confirm(\"Delete item?\")'>Delete</a></td>
                </tr>";
            }
        }
        echo '</tbody></table></div>';
    }

    private function render_builder(): void {
        global $wpdb;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $inv = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_invoices WHERE id = %d", $id)) : null;
        
        $customers = $wpdb->get_results("SELECT id, first_name, last_name FROM {$wpdb->prefix}studiofy_customers");
        $projects = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}studiofy_projects");
        $saved_items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}studiofy_items ORDER BY title ASC"); // For dropdown
        
        $inv_num = $inv ? $inv->invoice_number : 'INV-' . strtoupper(uniqid());
        
        $line_items = [];
        if ($inv && !empty($inv->line_items)) {
            $decoded = json_decode($inv->line_items, true);
            if (is_array($decoded)) $line_items = $decoded;
        }
        
        $tax_amount = $inv ? (float)$inv->tax_amount : 0;
        $subtotal = $inv ? (float)$inv->amount - $tax_amount : 0;
        $tax_rate = ($subtotal > 0) ? ($tax_amount / $subtotal) * 100 : 6.00; // Default to 6%
        
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
        wp_redirect(admin_url('admin.php?page=studiofy-invoices')); exit;
    }

    public function handle_save_item(): void {
        check_admin_referer('save_item', 'studiofy_nonce');
        global $wpdb;
        $wpdb->insert($wpdb->prefix.'studiofy_items', [
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_text_field($_POST['description']),
            'rate' => (float)$_POST['rate'],
            'rate_type' => sanitize_text_field($_POST['rate_type']),
            'default_qty' => (int)$_POST['default_qty'],
            'tax_rate' => (float)$_POST['tax_rate']
        ]);
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
        global $wpdb;
        $inv = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_invoices WHERE id=%d", $_GET['id']));
        echo "<html><head><title>Invoice {$inv->invoice_number}</title><style>body{font-family:sans-serif; padding:40px;} table{width:100%; border-collapse:collapse;} th,td{border:1px solid #ccc; padding:8px;} .header{text-align:center; margin-bottom:40px;}</style></head><body>";
        echo "<div class='header'><h1>INVOICE</h1><h2>#{$inv->invoice_number}</h2></div>";
        echo "<p><strong>Date:</strong> {$inv->issue_date}<br><strong>Due:</strong> {$inv->due_date}</p>";
        echo "<table><thead><tr><th>Description</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead><tbody>";
        $items = (!empty($inv->line_items)) ? json_decode($inv->line_items, true) : [];
        if(is_array($items)) {
            foreach($items as $i) {
                $amt = number_format((float)$i['qty'] * (float)$i['rate'], 2);
                echo "<tr><td>".esc_html($i['desc'] ?? '')."</td><td>".esc_html($i['qty'])."</td><td>".esc_html($i['rate'])."</td><td>$$amt</td></tr>";
            }
        }
        echo "</tbody></table><h3 style='text-align:right'>Total: $".number_format((float)$inv->amount, 2)."</h3><script>window.print();</script></body></html>";
        exit;
    }
}
