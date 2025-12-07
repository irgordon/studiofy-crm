<?php
/**
 * Billing Controller (Unified Contract & Invoice)
 * @package Studiofy\Admin
 * @version 2.3.9
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
        add_action('admin_post_studiofy_print_billing', [$this, 'handle_print']);
        add_action('admin_post_studiofy_email_contract', [$this, 'handle_email_contract']);
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
        
        $sql = "SELECT i.*, c.first_name, c.last_name, c.email FROM {$wpdb->prefix}studiofy_invoices i LEFT JOIN {$wpdb->prefix}studiofy_customers c ON i.customer_id = c.id ORDER BY i.$orderby $order";
        $rows = $wpdb->get_results($sql);
        
        $sig_page_query = new \WP_Query(['post_type'=>'page', 'title'=>'Signature', 'post_status'=>'all', 'posts_per_page'=>1]);
        $sig_page = $sig_page_query->have_posts() ? $sig_page_query->posts[0] : null;
        $base_sig_url = $sig_page ? get_permalink($sig_page->ID) : home_url('/signature/');

        echo '<div class="wrap"><h1 class="wp-heading-inline">Billing & Contracts</h1>';
        echo '<a href="?page=studiofy-billing&action=create" class="page-title-action">Create New</a>';
        echo '<hr class="wp-header-end">';
        
        if (empty($rows)) {
            echo '<div class="studiofy-empty-card"><h2>No billing records found</h2><a href="?page=studiofy-billing&action=create" class="button button-primary">Create First Record</a></div>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>ID</th><th>Customer</th><th>Title</th><th>Status</th><th>Contract</th><th>Actions</th></tr></thead><tbody>';
            foreach ($rows as $r) {
                $customer = $r->first_name ? esc_html($r->first_name . ' ' . $r->last_name) : 'Unknown';
                $edit_url = "?page=studiofy-billing&action=edit&id={$r->id}";
                $del_url = wp_nonce_url(admin_url("admin-post.php?action=studiofy_delete_billing&id={$r->id}"), 'delete_billing_'.$r->id);
                $view_url = wp_nonce_url(admin_url("admin-post.php?action=studiofy_print_billing&id={$r->id}"), 'print_billing_'.$r->id);
                $email_url = wp_nonce_url(admin_url("admin-post.php?action=studiofy_email_contract&id={$r->id}"), 'email_contract_'.$r->id);
                
                // Use Token if available, else ID (legacy fallback)
                $query_arg = !empty($r->access_token) ? "token={$r->access_token}" : "bid={$r->id}";
                $sig_url = $base_sig_url . '?' . $query_arg;
                
                echo "<tr>
                    <td>" . esc_html($r->id) . "</td>
                    <td><strong>" . $customer . "</strong></td>
                    <td>" . esc_html($r->title) . "</td>
                    <td><span class='studiofy-badge " . esc_attr(strtolower($r->status)) . "'>" . esc_html($r->status) . "</span></td>
                    <td><span class='studiofy-badge " . esc_attr(strtolower($r->contract_status)) . "'>" . esc_html($r->contract_status) . "</span></td>
                    <td>
                        <a href='$view_url' class='button button-small' target='_blank'>View</a>
                        <a href='$edit_url' class='button button-small'>Edit</a>
                        <a href='$sig_url' target='_blank' class='button button-small'>Sign Link</a>
                        <a href='$email_url' class='button button-small' onclick='return confirm(\"Email contract to client?\")'>Email</a>
                        <a href='$del_url' class='button button-small button-link-delete' onclick='return confirm(\"Delete?\")'>Delete</a>
                    </td>
                </tr>";
            }
            echo '</tbody></table>';
        }
        echo '</div>';
    }

    public function handle_email_contract(): void {
        check_admin_referer('email_contract_'.$_GET['id']);
        global $wpdb;
        $id = (int)$_GET['id'];
        $row = $wpdb->get_row($wpdb->prepare("SELECT i.*, c.email, c.first_name, c.last_name FROM {$wpdb->prefix}studiofy_invoices i JOIN {$wpdb->prefix}studiofy_customers c ON i.customer_id=c.id WHERE i.id=%d", $id));
        
        if(!$row || empty($row->email)) wp_die('No email found.');
        
        // Generate Token if missing (security update)
        if (empty($row->access_token)) {
            $token = bin2hex(random_bytes(16)); // 32 chars
            $wpdb->update($wpdb->prefix.'studiofy_invoices', ['access_token'=>$token], ['id'=>$id]);
            $row->access_token = $token;
        }

        $sig_page_query = new \WP_Query(['post_type'=>'page', 'title'=>'Signature', 'post_status'=>'all', 'posts_per_page'=>1]);
        $sig_page = $sig_page_query->have_posts() ? $sig_page_query->posts[0] : null;
        $base = $sig_page ? get_permalink($sig_page->ID) : home_url('/signature/');
        $url = $base . '?token=' . $row->access_token;
        
        // Custom Branding
        $settings = (array) get_option('studiofy_branding', []);
        $business_name = !empty($settings['business_name']) ? $settings['business_name'] : 'Our Studio';
        $customer_name = $row->first_name . ' ' . $row->last_name;

        // Custom Message
        $subject = "Contract Agreement - $business_name";
        $message = "Dear $customer_name,\n\n";
        $message .= "Thank you for choosing $business_name to take care of your photography needs!\n\n";
        $message .= "Please click the following link: $url and this will finalize your payment and agreement to contract terms.\n\n";
        $message .= "Thank you again for your business!";

        wp_mail($row->email, $subject, $message);
        
        // Update Status to Sent
        $wpdb->update($wpdb->prefix.'studiofy_invoices', ['status' => 'Sent'], ['id' => $id]);
        
        wp_redirect(admin_url('admin.php?page=studiofy-billing&msg=sent')); exit;
    }

    private function render_builder(): void {
        global $wpdb;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($id) {
            $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_invoices WHERE id = %d", $id));
        } else {
            $data = new \stdClass();
            $data->id = 0; $data->customer_id = 0; $data->title = ''; $data->service_type = 'Portrait'; $data->amount = 0.00; $data->tax_amount = 0.00; $data->service_fee = 0.00; $data->deposit_amount = 0.00; $data->status = 'Draft'; $data->contract_status = 'Unsigned'; 
            
            $data->contract_body = '<h3>1. Contact Information</h3><p><strong>Photographer:</strong> [Your Name/Business]<br><strong>Client:</strong> [Client Name]<br>This identifies who is responsible for fulfilling the terms of the agreement.</p><h3>2. Scope of Work</h3><p>Nature of assignment: [Type of Photography]. Includes [Number] images to be delivered. Location: [Location]. Date: [Date].</p><h3>3. Payment Terms</h3><p>Total Cost: [Total Amount]. Deposit: [Deposit Amount] due upon signing. Final payment due by [Due Date].<br>Acceptable methods: [Credit Card, Check, Transfer].</p><h3>4. Usage Rights and Licensing</h3><p>Images are licensed for [Personal/Commercial] use. License is [Exclusive/Non-Exclusive]. No resale or third-party distribution without written consent.</p><h3>5. Delivery Timeline</h3><p>Post-production and editing will be completed within [Number] days. Final delivery via [Online Gallery/USB]. Format: [JPEG/High Res].</p><h3>6. Cancellation and Rescheduling Policy</h3><p>Cancellations require [Number] hours notice. Rescheduling fee: [Amount]. Deposits are non-refundable.</p><h3>7. Model and Property Releases</h3><p>Client is responsible for securing necessary model or property releases unless otherwise specified in writing.</p><h3>8. Liability and Indemnity Clause</h3><p>Photographer is not liable for compromised coverage due to causes beyond control (e.g., equipment failure, weather). Client agrees to indemnify Photographer against claims arising from the shoot.</p><h3>9. Force Majeure</h3><p>Neither party shall be liable for failure to perform due to unforeseen events such as natural disasters, illness, or other emergencies.</p><h3>10. Signature and Agreement Date</h3><p>By signing, both parties agree to the terms above.</p><p><strong>Photographer:</strong> __________________________ Date: ______________</p><p><strong>Client:</strong> __________________________ Date: ______________</p>';

            $data->payment_methods = '[]'; $data->memo = "Thank you for your business!"; $data->due_date = date('Y-m-d', strtotime('+30 days')); $data->line_items = '[]';
            $data->signed_name = ''; $data->signed_at = ''; $data->signature_serial = ''; $data->signature_data = '';
        }

        $customers = $wpdb->get_results("SELECT id, first_name, last_name FROM {$wpdb->prefix}studiofy_customers ORDER BY last_name ASC");
        $line_items = json_decode($data->line_items, true) ?: [];
        $active_methods = json_decode($data->payment_methods, true) ?: [];
        $is_locked = ($data->contract_status === 'Signed' || $data->status === 'Paid');
        
        require_once STUDIOFY_PATH . 'templates/admin/billing-builder.php';
    }

    public function handle_save(): void {
        check_admin_referer('save_billing', 'studiofy_nonce');
        global $wpdb;
        
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
        
        $discount_percent = isset($_POST['discount_percent']) ? (float)$_POST['discount_percent'] : 0;
        $discount_amount = $subtotal * ($discount_percent / 100);
        $taxable_amount = max(0, $subtotal - $discount_amount);
        $tax_rate = isset($_POST['tax_rate']) ? (float)$_POST['tax_rate'] : 0;
        $tax_amt = $taxable_amount * ($tax_rate / 100);
        $service_fee = isset($_POST['apply_service_fee']) ? ($taxable_amount * 0.03) : 0.00;
        $tip_amount = isset($_POST['tip_amount']) ? (float)$_POST['tip_amount'] : 0.00;
        $total = $taxable_amount + $tax_amt + $service_fee + $tip_amount;

        // Token Logic
        $token = isset($_POST['id']) && !empty($_POST['id']) 
            ? $wpdb->get_var($wpdb->prepare("SELECT access_token FROM {$wpdb->prefix}studiofy_invoices WHERE id=%d", $_POST['id'])) 
            : bin2hex(random_bytes(16));
        
        if (empty($token)) $token = bin2hex(random_bytes(16));

        $db_data = [
            'customer_id' => (int)$_POST['customer_id'],
            'title' => sanitize_text_field($_POST['title']),
            'service_type' => sanitize_text_field($_POST['service_type']),
            'contract_body' => wp_kses_post($_POST['contract_body']), 
            'contract_status' => sanitize_text_field($_POST['contract_status']),
            'amount' => $total,
            'tax_amount' => $tax_amt,
            'service_fee' => $service_fee,
            'deposit_amount' => (float)$_POST['deposit_amount'],
            'payment_methods' => json_encode($_POST['payment_methods'] ?? []),
            'line_items' => json_encode($cleaned_items),
            'due_date' => sanitize_text_field($_POST['final_due_date']),
            'memo' => sanitize_textarea_field($_POST['memo']),
            'status' => sanitize_text_field($_POST['payment_status']),
            'access_token' => $token
        ];

        if(!empty($_POST['id'])) {
            $wpdb->update($wpdb->prefix.'studiofy_invoices', $db_data, ['id'=>(int)$_POST['id']]);
        } else {
            $wpdb->insert($wpdb->prefix.'studiofy_invoices', array_merge($db_data, ['created_at' => current_time('mysql'), 'invoice_number' => 'INV-'.rand(1000,9999)]));
        }
        
        wp_redirect(admin_url('admin.php?page=studiofy-billing'));
        exit;
    }

    public function handle_delete(): void { check_admin_referer('delete_billing_'.$_GET['id']); global $wpdb; $wpdb->delete($wpdb->prefix.'studiofy_invoices', ['id'=>(int)$_GET['id']]); wp_redirect(admin_url('admin.php?page=studiofy-billing')); exit; }
    public function handle_print(): void { check_admin_referer('print_billing_'.$_GET['id']); global $wpdb; $id = (int)$_GET['id']; $invoice = $wpdb->get_row($wpdb->prepare("SELECT i.*, c.first_name, c.last_name, c.email, c.phone, c.company, c.address FROM {$wpdb->prefix}studiofy_invoices i LEFT JOIN {$wpdb->prefix}studiofy_customers c ON i.customer_id = c.id WHERE i.id = %d", $id)); if (!$invoice) wp_die('Record not found.'); $branding = (array) get_option('studiofy_branding', []); $business_name = !empty($branding['business_name']) ? (string)$branding['business_name'] : 'Photography Studio'; $business_logo_url = !empty($branding['business_logo']) ? (string)$branding['business_logo'] : ''; $admin_email = get_option('admin_email'); $enc = new Encryption(); $customer = new \stdClass(); $customer->name = esc_html($invoice->first_name . ' ' . $invoice->last_name); $customer->company = esc_html($invoice->company); $customer->address = esc_html($enc->decrypt($invoice->address)); $invoice->issue_date_formatted = date('m/d/Y', strtotime($invoice->created_at)); $invoice->due_date_formatted = date('m/d/Y', strtotime($invoice->due_date)); $invoice->payable_to = "Payable to " . esc_html($business_name) . " Upon Receipt"; $invoice->line_items_data = json_decode($invoice->line_items, true) ?: []; $invoice->subtotal = 0; foreach($invoice->line_items_data as $item) { $invoice->subtotal += ((float)$item['qty'] * (float)$item['rate']); } ob_start(); include STUDIOFY_PATH . 'templates/admin/invoice-template.php'; $html = ob_get_clean(); if (!empty($invoice->contract_body)) { $contract_html = '<div class="contract-page" style="page-break-before: always; margin-top:40px; padding-top:40px; border-top:2px solid #eee;"><h1 class="invoice-title">SERVICE AGREEMENT</h1><div class="contract-body">' . wp_kses_post($invoice->contract_body) . '</div>'; if ($invoice->contract_status === 'Signed') { $signature_data = isset($invoice->signature_data) ? (string)$invoice->signature_data : ''; $contract_html .= '<div class="signature-box" style="margin-top:40px; border:1px solid #ccc; padding:20px; text-align:center;">'; if (!empty($signature_data)) { $contract_html .= '<img src="' . esc_url($signature_data) . '" style="max-height:80px;"><br>'; } $contract_html .= '<strong>Signed By:</strong> ' . esc_html($invoice->signed_name) . '<br><strong>Date:</strong> ' . esc_html($invoice->signed_at) . '<br><small>Serial: ' . esc_html($invoice->signature_serial) . '</small></div>'; } $contract_html .= '</div>'; $html = str_replace('</body>', $contract_html . '</body>', $html); } $html .= '<script>window.onload = function() { window.print(); }</script>'; echo $html; exit; }
}
