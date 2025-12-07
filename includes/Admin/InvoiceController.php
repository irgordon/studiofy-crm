<?php
/**
 * Invoice Controller
 * @package Studiofy\Admin
 * @version 2.2.42
 */

declare(strict_types=1);

namespace Studiofy\Admin;

use Studiofy\Utils\TableHelper;
use Dompdf\Dompdf;
use Dompdf\Options;

class InvoiceController {
    use TableHelper;

    public function init(): void {
        add_action('admin_post_studiofy_save_invoice', [$this, 'handle_save']);
        add_action('admin_post_studiofy_delete_invoice', [$this, 'handle_delete']);
        add_action('admin_post_studiofy_bulk_invoice', [$this, 'handle_bulk']);
        add_action('admin_post_studiofy_print_invoice', [$this, 'handle_print_pdf']); // Add PDF print action
        add_action('admin_notices', [$this, 'display_notices']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    public function enqueue_scripts($hook): void {
        if (strpos($hook, 'studiofy-invoices') === false) return;
        // Enqueue script for line item management in the invoice form
        wp_enqueue_script('studiofy-invoice-js', STUDIOFY_URL . 'assets/js/invoice.js', ['jquery'], STUDIOFY_VERSION, true);
    }

    public function display_notices(): void {
        if (isset($_GET['msg'])) {
            $msg = '';
            switch ($_GET['msg']) {
                case 'saved': $msg = 'Invoice saved successfully.'; break;
                case 'deleted': $msg = 'Invoice(s) deleted.'; break;
                case 'nonce': $msg = 'Security check failed.'; break;
                case 'error': $msg = 'Error saving invoice. Check required fields.'; break;
            }
            if ($msg) echo "<div class='notice notice-success is-dismissible'><p>$msg</p></div>";
        }
    }

    public function render_page(): void {
        $action = $_GET['action'] ?? 'list';
        if ($action === 'new' || $action === 'edit' || $action === 'clone') {
            $this->render_form();
        } else {
            $this->render_list();
        }
    }

    private function render_list(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'studiofy_invoices';
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $orderby = $_GET['orderby'] ?? 'created_at';
        $order = strtoupper($_GET['order'] ?? 'DESC');
        
        $where = '';
        if ($search) {
            $where = $wpdb->prepare("WHERE title LIKE %s OR invoice_number LIKE %s", "%$search%", "%$search%");
        }

        $items = $wpdb->get_results("SELECT i.*, c.first_name, c.last_name FROM $table i LEFT JOIN {$wpdb->prefix}studiofy_customers c ON i.customer_id = c.id $where ORDER BY $orderby $order");
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Invoices</h1>
            <a href="?page=studiofy-invoices&action=new" class="page-title-action">Add New</a>
            <hr class="wp-header-end">
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="studiofy_bulk_invoice">
                <?php wp_nonce_field('bulk_invoice', 'studiofy_nonce'); ?>
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <select name="bulk_action">
                            <option value="-1">Bulk Actions</option>
                            <option value="delete">Delete</option>
                        </select>
                        <button type="submit" class="button action">Apply</button>
                    </div>
                    <div class="alignright">
                        <input type="search" name="s" value="<?php echo esc_attr($search); ?>">
                        <button type="button" class="button" onclick="window.location.href='?page=studiofy-invoices&s='+this.previousElementSibling.value">Search</button>
                    </div>
                </div>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column"><input type="checkbox"></td>
                            <th class="manage-column sortable"><?php echo $this->sort_link('Invoice #', 'invoice_number'); ?></th>
                            <th class="manage-column sortable"><?php echo $this->sort_link('Title', 'title'); ?></th>
                            <th class="manage-column">Customer</th>
                            <th class="manage-column sortable"><?php echo $this->sort_link('Amount', 'amount'); ?></th>
                            <th class="manage-column sortable"><?php echo $this->sort_link('Status', 'status'); ?></th>
                            <th class="manage-column sortable"><?php echo $this->sort_link('Due Date', 'due_date'); ?></th>
                            <th class="manage-column">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr><td colspan="8">No invoices found.</td></tr>
                        <?php else: foreach ($items as $item): 
                            $edit_url = "?page=studiofy-invoices&action=edit&id={$item->id}";
                            $clone_url = "?page=studiofy-invoices&action=clone&id={$item->id}";
                            $print_url = wp_nonce_url(admin_url('admin-post.php?action=studiofy_print_invoice&id=' . $item->id), 'print_invoice_' . $item->id);
                            $del_url = wp_nonce_url(admin_url('admin-post.php?action=studiofy_delete_invoice&id=' . $item->id), 'delete_invoice_' . $item->id);
                            $cust_name = $item->first_name ? esc_html($item->first_name . ' ' . $item->last_name) : 'Unassigned';
                            $due_date = $item->due_date ? date('m/d/Y', strtotime($item->due_date)) : 'N/A'; // Standardized Date Format
                        ?>
                            <tr>
                                <th scope="row" class="check-column"><input type="checkbox" name="ids[]" value="<?php echo $item->id; ?>"></th>
                                <td><strong><a href="<?php echo $edit_url; ?>"><?php echo esc_html($item->invoice_number); ?></a></strong></td>
                                <td><?php echo esc_html($item->title); ?></td>
                                <td><?php echo $cust_name; ?></td>
                                <td><?php echo esc_html($item->currency . ' ' . number_format((float)$item->amount, 2)); ?></td>
                                <td><span class="studiofy-badge <?php echo esc_attr(strtolower($item->status)); ?>"><?php echo esc_html($item->status); ?></span></td>
                                <td><?php echo $due_date; ?></td>
                                <td>
                                    <a href="<?php echo $edit_url; ?>" class="button button-small">Edit</a>
                                    <a href="<?php echo $clone_url; ?>" class="button button-small">Clone</a>
                                    <a href="<?php echo $print_url; ?>" class="button button-small" target="_blank">Print PDF</a>
                                    <a href="<?php echo $del_url; ?>" onclick="return confirm('Delete this invoice?')" class="button button-small" style="color:#b32d2e;">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </form>
        </div>
        <?php
    }

    private function render_form(): void {
        global $wpdb;
        $data = null;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $is_clone = isset($_GET['action']) && $_GET['action'] === 'clone';
        
        if ($id) {
            $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_invoices WHERE id = %d", $id));
        }

        $title = $is_clone ? "Clone Invoice" : ($data ? "Edit Invoice" : "Create New Invoice");
        $btn_text = $is_clone ? "Create Clone" : ($data ? "Update Invoice" : "Create Invoice");

        if ($is_clone) {
            $data->id = ''; 
            $data->invoice_number = 'INV-' . strtoupper(substr(md5(uniqid()), 0, 6)); // Generate new number for clone
            $data->title .= ' (Clone)';
            $data->status = 'Draft';
        } elseif (!$data) {
            // Defaults for new invoice
            $data = new \stdClass();
            $data->invoice_number = 'INV-' . strtoupper(substr(md5(uniqid()), 0, 6));
            $data->status = 'Draft';
            $data->issue_date = date('Y-m-d');
            $data->due_date = date('Y-m-d', strtotime('+30 days'));
            $data->currency = 'USD';
            $data->tax_amount = 0.00;
            $data->line_items = '[]';
        }

        $customers = $wpdb->get_results("SELECT id, first_name, last_name FROM {$wpdb->prefix}studiofy_customers ORDER BY last_name ASC");
        $projects = $wpdb->get_results("SELECT id, title, customer_id FROM {$wpdb->prefix}studiofy_projects ORDER BY title ASC");
        $line_items = json_decode($data->line_items, true) ?: [];

        ?>
        <div class="wrap">
            <h1><?php echo $title; ?></h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="studiofy-card-form">
                <input type="hidden" name="action" value="studiofy_save_invoice">
                <?php wp_nonce_field('save_invoice', 'studiofy_nonce'); ?>
                <?php if ($data->id) echo '<input type="hidden" name="id" value="' . $data->id . '">'; ?>

                <div class="studiofy-form-grid">
                    <div class="studiofy-col-2">
                        <h2 style="margin-top:0;">Invoice Details</h2>
                        <table class="form-table">
                            <tr><th><label for="invoice_number">Invoice #</label></th><td><input type="text" name="invoice_number" id="invoice_number" class="regular-text" required value="<?php echo esc_attr($data->invoice_number); ?>"></td></tr>
                            <tr><th><label for="title">Title</label></th><td><input type="text" name="title" id="title" class="regular-text" required value="<?php echo esc_attr($data->title ?? ''); ?>"></td></tr>
                            <tr>
                                <th><label for="customer_id">Customer</label></th>
                                <td>
                                    <select name="customer_id" id="customer_id" class="regular-text" required>
                                        <option value="">Select Customer</option>
                                        <?php foreach ($customers as $c): ?>
                                            <option value="<?php echo $c->id; ?>" <?php selected($data->customer_id ?? '', $c->id); ?>><?php echo esc_html($c->first_name . ' ' . $c->last_name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="project_id">Project (Optional)</label></th>
                                <td>
                                    <select name="project_id" id="project_id" class="regular-text">
                                        <option value="">None</option>
                                        <?php foreach ($projects as $p): ?>
                                            <option value="<?php echo $p->id; ?>" data-customer="<?php echo $p->customer_id; ?>" <?php selected($data->project_id ?? '', $p->id); ?>><?php echo esc_html($p->title); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="status">Status</label></th>
                                <td>
                                    <select name="status" id="status" class="regular-text">
                                        <option <?php selected($data->status, 'Draft'); ?>>Draft</option>
                                        <option <?php selected($data->status, 'Sent'); ?>>Sent</option>
                                        <option <?php selected($data->status, 'Paid'); ?>>Paid</option>
                                        <option <?php selected($data->status, 'Overdue'); ?>>Overdue</option>
                                        <option <?php selected($data->status, 'Void'); ?>>Void</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="studiofy-col-2">
                        <h2 style="margin-top:0;">Dates & Currency</h2>
                        <table class="form-table">
                            <tr><th><label for="issue_date">Issue Date</label></th><td><input type="date" name="issue_date" id="issue_date" class="regular-text" required value="<?php echo esc_attr($data->issue_date); ?>"></td></tr>
                            <tr><th><label for="due_date">Due Date</label></th><td><input type="date" name="due_date" id="due_date" class="regular-text" required value="<?php echo esc_attr($data->due_date); ?>"></td></tr>
                            <tr>
                                <th><label for="currency">Currency</label></th>
                                <td>
                                    <select name="currency" id="currency" class="regular-text">
                                        <option value="USD" <?php selected($data->currency, 'USD'); ?>>USD ($)</option>
                                        <option value="EUR" <?php selected($data->currency, 'EUR'); ?>>EUR (€)</option>
                                        <option value="GBP" <?php selected($data->currency, 'GBP'); ?>>GBP (£)</option>
                                        <option value="CAD" <?php selected($data->currency, 'CAD'); ?>>CAD ($)</option>
                                    </select>
                                </td>
                            </tr>
                             <tr><th><label for="tax_amount">Tax Amount</label></th><td><input type="number" step="0.01" name="tax_amount" id="tax_amount" class="regular-text" value="<?php echo esc_attr($data->tax_amount); ?>"></td></tr>
                        </table>
                    </div>
                </div>
                
                <hr>
                
                <h2>Line Items</h2>
                <table class="wp-list-table widefat fixed striped" id="invoice-line-items">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Description</th>
                            <th style="width: 15%;">Quantity</th>
                            <th style="width: 20%;">Unit Price</th>
                            <th style="width: 15%;">Total</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($line_items as $idx => $item): ?>
                            <tr>
                                <td><input type="text" name="line_items[<?php echo $idx; ?>][desc]" value="<?php echo esc_attr($item['desc']); ?>" class="widefat" required></td>
                                <td><input type="number" step="0.01" name="line_items[<?php echo $idx; ?>][qty]" value="<?php echo esc_attr($item['qty']); ?>" class="widefat qty-input" required></td>
                                <td><input type="number" step="0.01" name="line_items[<?php echo $idx; ?>][rate]" value="<?php echo esc_attr($item['rate']); ?>" class="widefat rate-input" required></td>
                                <td><span class="line-total"><?php echo number_format((float)$item['qty'] * (float)$item['rate'], 2); ?></span></td>
                                <td><button type="button" class="button button-small delete-row" aria-label="Delete Row">&times;</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><button type="button" class="button" id="add-line-item">Add Line Item</button></p>
                
                <div class="studiofy-invoice-totals" style="text-align: right; margin-top: 20px;">
                    <p><strong>Subtotal:</strong> <span id="invoice-subtotal">0.00</span></p>
                    <p><strong>Tax:</strong> <span id="invoice-tax-display"><?php echo number_format((float)$data->tax_amount, 2); ?></span></p>
                    <p style="font-size: 1.2em;"><strong>Total:</strong> <span id="invoice-total-display">0.00</span></p>
                    <input type="hidden" name="amount" id="invoice-total-amount" value="<?php echo esc_attr($data->amount ?? 0.00); ?>">
                </div>
                
                <hr>
                <p class="submit"><button type="submit" class="button button-primary"><?php echo $btn_text; ?></button></p>
            </form>
        </div>
        <script>
            // Simple JS to handle Customer/Project filtering
            jQuery(document).ready(function($){
                $('#customer_id').change(function(){
                    var cid = $(this).val();
                    $('#project_id option').each(function(){
                        var pcid = $(this).data('customer');
                        if(cid === '' || pcid == cid || $(this).val() === '') {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                    if($('#project_id option:selected').css('display') === 'none'){
                        $('#project_id').val('');
                    }
                });
                // Trigger on load to filter projects if customer is selected
                if($('#customer_id').val()){ $('#customer_id').change(); }
            });
        </script>
        <?php
    }

    public function handle_save(): void {
        check_admin_referer('save_invoice', 'studiofy_nonce');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        
        global $wpdb;
        
        $line_items = $_POST['line_items'] ?? [];
        $cleaned_items = [];
        $subtotal = 0;
        foreach ($line_items as $item) {
            $qty = (float)$item['qty'];
            $rate = (float)$item['rate'];
            $cleaned_items[] = [
                'desc' => sanitize_text_field($item['desc']),
                'qty' => $qty,
                'rate' => $rate
            ];
            $subtotal += ($qty * $rate);
        }
        
        $tax_amount = (float)$_POST['tax_amount'];
        $total_amount = $subtotal + $tax_amount;

        $data = [
            'invoice_number' => sanitize_text_field($_POST['invoice_number']),
            'customer_id' => (int)$_POST['customer_id'],
            'project_id' => !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null,
            'title' => sanitize_text_field($_POST['title']),
            'amount' => $total_amount, // Use calculated total
            'tax_amount' => $tax_amount,
            'line_items' => json_encode($cleaned_items),
            'status' => sanitize_text_field($_POST['status']),
            'issue_date' => sanitize_text_field($_POST['issue_date']),
            'due_date' => sanitize_text_field($_POST['due_date']),
            'currency' => sanitize_text_field($_POST['currency'])
        ];

        if (!empty($_POST['id'])) {
            $wpdb->update($wpdb->prefix.'studiofy_invoices', $data, ['id' => (int)$_POST['id']]);
        } else {
            $wpdb->insert($wpdb->prefix.'studiofy_invoices', array_merge($data, ['created_at' => current_time('mysql')]));
        }
        
        wp_redirect(admin_url('admin.php?page=studiofy-invoices&msg=saved')); exit;
    }
    
    public function handle_print_pdf(): void {
        if (!isset($_GET['id'])) wp_die('Invoice ID missing.');
        check_admin_referer('print_invoice_' . $_GET['id']);
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        global $wpdb;
        $id = (int)$_GET['id'];
        $invoice = $wpdb->get_row($wpdb->prepare("SELECT i.*, c.first_name, c.last_name, c.email, c.phone, c.company, c.address FROM {$wpdb->prefix}studiofy_invoices i LEFT JOIN {$wpdb->prefix}studiofy_customers c ON i.customer_id = c.id WHERE i.id = %d", $id));

        if (!$invoice) wp_die('Invoice not found.');

        // Get Business Settings for Layout & "Payable To"
        $branding = (array) get_option('studiofy_branding', []);
        $business_name = !empty($branding['business_name']) ? (string)$branding['business_name'] : 'Photography Studio';
        $business_logo_url = !empty($branding['business_logo']) ? (string)$branding['business_logo'] : '';

        // Decrypt Customer Data
        $enc = new \Studiofy\Security\Encryption();
        $customer = new \stdClass();
        $customer->name = esc_html($invoice->first_name . ' ' . $invoice->last_name);
        $customer->company = esc_html($invoice->company);
        $customer->address = esc_html($enc->decrypt($invoice->address));
        $customer->email = esc_html($invoice->email);
        $customer->phone = esc_html($enc->decrypt($invoice->phone));
        
        // Standardize Date Format (MM/DD/YYYY)
        $invoice->issue_date_formatted = date('m/d/Y', strtotime($invoice->issue_date));
        $invoice->due_date_formatted = date('m/d/Y', strtotime($invoice->due_date));

        // Standardize Payment Instruction
        $invoice->payable_to = "Payable to the " . esc_html($business_name) . " Upon Receipt";
        
        // Prepare Line Items
        $invoice->line_items_data = json_decode($invoice->line_items, true) ?: [];
        $invoice->subtotal = 0;
        foreach($invoice->line_items_data as $item) {
            $invoice->subtotal += ((float)$item['qty'] * (float)$item['rate']);
        }

        // Load HTML Template
        ob_start();
        // Path to the new invoice template file
        include STUDIOFY_PATH . 'templates/admin/invoice-template.php';
        $html = ob_get_clean();

        // Generate PDF with Dompdf
        require_once STUDIOFY_PATH . 'vendor/autoload.php';
        $options = new Options();
        $options->set('isRemoteEnabled', true); // Enable for logo image
        $options->set('defaultFont', 'Helvetica');
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Stream the PDF to the browser
        $dompdf->stream('Invoice-' . $invoice->invoice_number . '.pdf', ['Attachment' => false]);
        exit;
    }

    public function handle_bulk(): void {
        check_admin_referer('bulk_invoice', 'studiofy_nonce');
        if ($_POST['bulk_action'] === 'delete' && !empty($_POST['ids'])) {
            global $wpdb;
            $ids = array_map('intval', $_POST['ids']);
            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}studiofy_invoices WHERE id IN ($placeholders)", $ids));
            wp_redirect(admin_url('admin.php?page=studiofy-invoices&msg=deleted')); exit;
        }
        wp_redirect(admin_url('admin
