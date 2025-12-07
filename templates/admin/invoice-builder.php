<?php
/**
 * Invoice Builder / Edit Form
 * @version 2.2.48
 * * Data Context:
 * $invoice (object) - The main invoice object
 * $customer (object) - Hydrated customer data
 * $customers (array) - List for dropdown
 * $projects (array) - List for dropdown
 * $saved_items (array) - List for library
 */
?>
<div class="wrap">
    <h1><?php echo $invoice->id ? 'Edit Invoice' : 'Create New Invoice'; ?></h1>
    
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="studiofy-card-form">
        <input type="hidden" name="action" value="studiofy_save_invoice">
        <?php wp_nonce_field('save_invoice', 'studiofy_nonce'); ?>
        <?php if ($invoice->id) echo '<input type="hidden" name="id" value="' . $invoice->id . '">'; ?>

        <div class="studiofy-form-grid">
            <div class="studiofy-col-2">
                <h2 style="margin-top:0;">Invoice Details</h2>
                <table class="form-table">
                    <tr><th><label for="invoice_number">Invoice Number</label></th><td><input type="text" name="invoice_number" id="invoice_number" class="regular-text" required value="<?php echo esc_attr($invoice->invoice_number); ?>"></td></tr>
                    <tr><th><label for="title">Invoice Title</label></th><td><input type="text" name="title" id="title" class="regular-text" placeholder="e.g. Wedding Package" value="<?php echo esc_attr($invoice->title); ?>"></td></tr>
                    <tr>
                        <th><label for="status">Status</label></th>
                        <td>
                            <select name="status" id="status" class="regular-text">
                                <option <?php selected($invoice->status, 'Draft'); ?>>Draft</option>
                                <option <?php selected($invoice->status, 'Sent'); ?>>Sent</option>
                                <option <?php selected($invoice->status, 'Paid'); ?>>Paid</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="studiofy-col-2">
                <h2 style="margin-top:0;">Client & Dates</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="customer_id">Customer</label></th>
                        <td>
                            <select name="customer_id" id="customer_id" class="regular-text" required>
                                <option value="">Select Customer</option>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?php echo $c->id; ?>" <?php selected($invoice->customer_id, $c->id); ?>>
                                        <?php echo esc_html($c->first_name . ' ' . $c->last_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="project_id">Project</label></th>
                        <td>
                            <select name="project_id" id="project_id" class="regular-text">
                                <option value="">None</option>
                                <?php foreach ($projects as $p): ?>
                                    <option value="<?php echo $p->id; ?>" data-customer="<?php echo $p->customer_id; ?>" <?php selected($invoice->project_id, $p->id); ?>>
                                        <?php echo esc_html($p->title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr><th><label for="issue_date">Issue Date</label></th><td><input type="date" name="issue_date" id="issue_date" class="regular-text" value="<?php echo esc_attr($invoice->issue_date); ?>"></td></tr>
                    <tr><th><label for="due_date">Due Date</label></th><td><input type="date" name="due_date" id="due_date" class="regular-text" value="<?php echo esc_attr($invoice->due_date); ?>"></td></tr>
                </table>
            </div>
        </div>

        <hr>

        <div class="studiofy-panel">
            <h3>Line Items</h3>
            
            <div style="margin-bottom:15px; display:flex; align-items:center; gap:10px;">
                <select id="item_select" style="max-width:250px;">
                    <option value="">Load from Item Library...</option>
                    <?php foreach($saved_items as $si): ?>
                        <option value="<?php echo $si->id; ?>" 
                                data-desc="<?php echo esc_attr($si->description); ?>" 
                                data-rate="<?php echo esc_attr($si->rate); ?>" 
                                data-qty="<?php echo esc_attr($si->default_qty); ?>">
                            <?php echo esc_html($si->title); ?> - $<?php echo esc_html($si->rate); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button" id="add-item-btn">+ Add Blank Row</button>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 40%;">Description</th>
                        <th style="width: 10%;">Qty</th>
                        <th style="width: 15%;">Rate</th>
                        <th style="width: 15%;">Amount</th>
                        <th style="width: 5%;"></th>
                    </tr>
                </thead>
                <tbody id="line-items-body">
                    <?php 
                    $lines = !empty($invoice->line_items_data) ? $invoice->line_items_data : [];
                    foreach($lines as $idx => $item): ?>
                    <tr>
                        <td><input type="text" name="items[<?php echo $idx; ?>][desc]" class="widefat item-desc" value="<?php echo esc_attr($item['desc']); ?>" required></td>
                        <td><input type="number" step="0.01" name="items[<?php echo $idx; ?>][qty]" class="widefat item-qty" value="<?php echo esc_attr($item['qty']); ?>" required></td>
                        <td><input type="number" step="0.01" name="items[<?php echo $idx; ?>][rate]" class="widefat item-rate" value="<?php echo esc_attr($item['rate']); ?>" required></td>
                        <td><span class="item-total">$<?php echo number_format((float)$item['qty'] * (float)$item['rate'], 2); ?></span></td>
                        <td><button type="button" class="button-link-delete remove-row">&times;</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
                <table style="width: 300px;">
                    <tr>
                        <td style="text-align:right; padding-right:10px;"><strong>Subtotal:</strong></td>
                        <td style="text-align:right;">$<span id="invoice-subtotal"><?php echo number_format($invoice->subtotal, 2); ?></span></td>
                    </tr>
                    <tr>
                        <td style="text-align:right; padding-right:10px;">
                            <strong>Tax Rate (%):</strong><br>
                            <input type="number" step="0.01" name="tax_rate" id="tax-rate" value="<?php echo esc_attr($tax_rate); ?>" style="width:60px; text-align:right;">
                        </td>
                        <td style="text-align:right; vertical-align:bottom;">
                            $<span id="invoice-tax-display"><?php echo number_format((float)$invoice->tax_amount, 2); ?></span>
                            <input type="hidden" name="tax_amount" id="input_tax_amount" value="<?php echo esc_attr($invoice->tax_amount); ?>">
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align:right; padding-right:10px; font-size:1.2em;"><strong>Total:</strong></td>
                        <td style="text-align:right; font-size:1.2em;"><strong>$<span id="invoice-total-display"><?php echo number_format((float)$invoice->amount, 2); ?></span></strong></td>
                    </tr>
                </table>
            </div>
        </div>

        <p class="submit">
            <button type="submit" class="button button-primary button-large">Save Invoice</button>
            <a href="<?php echo admin_url('admin.php?page=studiofy-invoices'); ?>" class="button">Cancel</a>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($){
    let rowIdx = <?php echo count($lines); ?>;

    // Filter Projects
    $('#customer_id').change(function(){
        let cid = $(this).val();
        $('#project_id option').each(function(){
            let pcid = $(this).data('customer');
            if(cid === '' || pcid == cid || $(this).val() === '') $(this).show();
            else $(this).hide();
        });
        if($('#project_id option:selected').css('display') === 'none') $('#project_id').val('');
    }).trigger('change');

    function addRow(desc = '', qty = 1, rate = 0.00) {
        let html = `<tr>
            <td><input type="text" name="items[${rowIdx}][desc]" class="widefat item-desc" value="${desc}"></td>
            <td><input type="number" step="0.01" name="items[${rowIdx}][qty]" class="widefat item-qty" value="${qty}"></td>
            <td><input type="number" step="0.01" name="items[${rowIdx}][rate]" class="widefat item-rate" value="${rate}"></td>
            <td><span class="item-total">$0.00</span></td>
            <td><button type="button" class="button-link-delete remove-row">&times;</button></td>
        </tr>`;
        $('#line-items-body').append(html);
        rowIdx++;
        calcTotal();
    }

    // Load from Library
    $('#item_select').change(function(){
        let opt = $(this).find(':selected');
        if(opt.val() !== '') {
            addRow(opt.text().split(' - $')[0], opt.data('qty'), opt.data('rate'));
            $(this).val('');
        }
    });

    $('#add-item-btn').click(function(){ addRow(); });
    $(document).on('click', '.remove-row', function(){ $(this).closest('tr').remove(); calcTotal(); });
    $(document).on('input', '.item-qty, .item-rate, #tax-rate', function(){ calcTotal(); });

    function calcTotal() {
        let sub = 0;
        $('#line-items-body tr').each(function(){
            let qty = parseFloat($(this).find('.item-qty').val()) || 0;
            let rate = parseFloat($(this).find('.item-rate').val()) || 0;
            let amt = qty * rate;
            $(this).find('.item-total').text('$' + amt.toFixed(2));
            sub += amt;
        });
        $('#invoice-subtotal').text(sub.toFixed(2));
        
        let taxRate = parseFloat($('#tax-rate').val()) || 0;
        let taxAmt = sub * (taxRate / 100);
        $('#invoice-tax-display').text(taxAmt.toFixed(2));
        $('#input_tax_amount').val(taxAmt.toFixed(2));
        
        let total = sub + taxAmt;
        $('#invoice-total-display').text(total.toFixed(2));
    }
});
</script>
