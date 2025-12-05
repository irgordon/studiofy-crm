<?php
declare(strict_types=1);
namespace Studiofy\Admin;

class InvoiceController {
    public function init(): void {
        add_action('admin_post_studiofy_save_invoice', [$this, 'handle_save']);
    }

    public function render_page(): void {
        if (($action = $_GET['action'] ?? '') === 'create') {
            $this->render_builder();
        } else {
            $this->render_list();
        }
    }

    private function render_list(): void {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}studiofy_invoices ORDER BY created_at DESC");
        ?>
        <div class="wrap studiofy-dark-theme">
            <h1>Invoices <a href="?page=studiofy-invoices&action=create" class="page-title-action">New Invoice</a></h1>
            <?php if(empty($rows)): ?><div class="studiofy-empty-state"><p>No invoices yet.</p></div><?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Number</th><th>Title</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody><?php foreach($rows as $r) echo "<tr><td>{$r->invoice_number}</td><td>{$r->title}</td><td>\${$r->amount}</td><td>{$r->status}</td></tr>"; ?></tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_builder(): void {
        global $wpdb;
        $clients = $wpdb->get_results("SELECT id, first_name, last_name FROM {$wpdb->prefix}studiofy_clients");
        $inv_num = 'INV-' . rand(1000,9999);
        require_once STUDIOFY_PATH . 'templates/admin/invoice-builder.php';
    }

    public function handle_save(): void {
        check_admin_referer('save_invoice', 'studiofy_nonce');
        global $wpdb;
        $items = $_POST['items'] ?? [];
        $subtotal = 0;
        foreach($items as $i) $subtotal += ((float)$i['qty'] * (float)$i['rate']);
        
        $tax = (float)($_POST['tax_amount'] ?? 0);
        $total = $subtotal + $tax;

        $wpdb->insert($wpdb->prefix.'studiofy_invoices', [
            'invoice_number' => sanitize_text_field($_POST['invoice_number']),
            'client_id' => (int)$_POST['client_id'],
            'status' => sanitize_text_field($_POST['status']),
            'issue_date' => sanitize_text_field($_POST['issue_date']),
            'due_date' => sanitize_text_field($_POST['due_date']),
            'title' => 'Invoice ' . $_POST['invoice_number'],
            'amount' => $total,
            'tax_amount' => $tax,
            'line_items' => json_encode($items)
        ]);
        wp_redirect(admin_url('admin.php?page=studiofy-invoices')); exit;
    }
}
