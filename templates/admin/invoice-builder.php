<div class="wrap">
    <h1><?php echo $inv ? 'Edit Invoice' : 'New Invoice'; ?></h1>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="studiofy_save_invoice">
        <?php wp_nonce_field('save_invoice', 'studiofy_nonce'); ?>
        <?php if($inv) echo '<input type="hidden" name="id" value="'.$inv->id.'">'; ?>

        <div class="studiofy-panel">
            <div class="studiofy-form-row">
                <div class="studiofy-col"><label>Invoice Number</label><input type="text" name="invoice_number" value="<?php echo esc_attr($inv_num); ?>" readonly class="widefat"></div>
                <div class="studiofy-col"><label>Status</label>
                    <select name="status" class="widefat">
                        <option value="Draft" <?php selected($inv->status??'', 'Draft'); ?>>Draft</option>
                        <option value="Sent" <?php selected($inv->status??'', 'Sent'); ?>>Sent</option>
                        <option value="Paid" <?php selected($inv->status??'', 'Paid'); ?>>Paid</option>
                    </select>
                </div>
            </div>
            <div class="studiofy-form-row">
                <div class="studiofy-col"><label>Customer *</label>
                    <select name="customer_id" required class="widefat">
                        <option value="">Select Customer</option>
                        <?php foreach($customers as $c) echo "<option value='{$c->id}' ".selected($inv->customer_id??0, $c->id, false).">{$c->first_name} {$c->last_name}</option>"; ?>
                    </select>
                </div>
                <div class="studiofy-col"><label>Project</label>
                    <select name="project_id" class="widefat">
                        <option value="">Select Project</option>
                        <?php foreach($projects as $p) echo "<option value='{$p->id}' ".selected($inv->project_id??0, $p->id, false).">{$p->title}</option>"; ?>
                    </select>
                </div>
            </div>
            <div class="studiofy-form-row">
                <div class="studiofy-col"><label>Issue Date</label><input type="date" name="issue_date" value="<?php echo $inv->issue_date??date('Y-m-d'); ?>" class="widefat"></div>
                <div class="studiofy-col"><label>Due Date</label><input type="date" name="due_date" value="<?php echo $inv->due_date??''; ?>" class="widefat"></div>
            </div>
        </div>

        <div class="studiofy-panel" style="margin-top:20px;">
            <h3>Line Items <button type="button" class="button" id="add-item" style="float:right;">+ Add Item</button></h3>
            <table class="widefat fixed striped">
                <thead><tr><th>Description</th><th width="10%">Qty</th><th width="15%">Rate</th><th width="15%">Amount</th><th width="5%"></th></tr></thead>
                <tbody id="line-items-body">
                    <?php if(!empty($line_items)): foreach($line_items as $k => $i): ?>
                    <tr>
                        <td><input type="text" name="items[<?php echo $k; ?>][desc]" class="widefat" value="<?php echo esc_attr($i['desc']); ?>"></td>
                        <td><input type="number" name="items[<?php echo $k; ?>][qty]" class="widefat qty" value="<?php echo esc_attr($i['qty']); ?>"></td>
                        <td><input type="number" step="0.01" name="items[<?php echo $k; ?>][rate]" class="widefat rate" value="<?php echo esc_attr($i['rate']); ?>"></td>
                        <td><input type="text" name="items[<?php echo $k; ?>][amount]" class="widefat amount" readonly value="<?php echo number_format($i['qty']*$i['rate'], 2); ?>"></td>
                        <td><span class="dashicons dashicons-trash remove-row" style="cursor:pointer; color:red;"></span></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
            <div style="text-align:right; padding:10px; font-size:16px;">
                Tax Rate (%): <input type="number" name="tax_rate" id="tax-rate" step="0.1" value="0" style="width:70px;"><br>
                <strong>Total: $<span id="invoice-total"><?php echo $inv ? $inv->amount : '0.00'; ?></span></strong>
            </div>
        </div>

        <p class="submit"><button type="submit" class="button button-primary button-large">Save Invoice</button></p>
    </form>
</div>

<script>
jQuery(document).ready(function($){
    let idx = <?php echo count($line_items ?? []); ?>;
    
    function addRow() {
        const row = `<tr>
            <td><input type="text" name="items[${idx}][desc]" class="widefat" placeholder="Item"></td>
            <td><input type="number" name="items[${idx}][qty]" class="widefat qty" value="1"></td>
            <td><input type="number" step="0.01" name="items[${idx}][rate]" class="widefat rate" value="0.00"></td>
            <td><input type="text" name="items[${idx}][amount]" class="widefat amount" readonly value="0.00"></td>
            <td><span class="dashicons dashicons-trash remove-row" style="cursor:pointer; color:red;"></span></td>
        </tr>`;
        $('#line-items-body').append(row);
        idx++;
    }
    
    $('#add-item').click(addRow);
    $(document).on('click', '.remove-row', function(){ $(this).closest('tr').remove(); calc(); });
    $(document).on('input', '.qty, .rate, #tax-rate', calc);

    function calc() {
        let subtotal = 0;
        $('.qty').each(function(){
            const row = $(this).closest('tr');
            const qty = parseFloat(row.find('.qty').val()) || 0;
            const rate = parseFloat(row.find('.rate').val()) || 0;
            const amt = qty * rate;
            row.find('.amount').val(amt.toFixed(2));
            subtotal += amt;
        });
        
        const taxRate = parseFloat($('#tax-rate').val()) || 0;
        const taxAmt = subtotal * (taxRate / 100);
        $('#invoice-total').text((subtotal + taxAmt).toFixed(2));
    }
    
    if(idx === 0) addRow();
});
</script>
