<div class="wrap">
    <h1><?php echo $inv ? 'Edit Invoice' : 'New Invoice'; ?></h1>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="studiofy_save_invoice">
        <?php wp_nonce_field('save_invoice', 'studiofy_nonce'); ?>
        <?php if($inv) echo '<input type="hidden" name="id" value="'.$inv->id.'">'; ?>

        <div class="studiofy-panel">
            <div class="studiofy-form-row">
                <div class="studiofy-col"><label for="inv_num">Invoice Number</label><input type="text" name="invoice_number" id="inv_num" value="<?php echo esc_attr($inv_num); ?>" readonly class="widefat"></div>
                <div class="studiofy-col"><label for="inv_status">Status</label><select name="status" id="inv_status" class="widefat"><option>Draft</option><option>Sent</option><option>Paid</option></select></div>
            </div>
            <div class="studiofy-form-row">
                <div class="studiofy-col"><label for="inv_cust">Customer</label><select name="customer_id" id="inv_cust" required class="widefat"><option value="">Select</option><?php foreach($customers as $c) echo "<option value='{$c->id}' ".selected($inv->customer_id ?? 0, $c->id, false).">{$c->first_name} {$c->last_name}</option>"; ?></select></div>
                <div class="studiofy-col"><label for="inv_proj">Project</label><select name="project_id" id="inv_proj" class="widefat"><option value="">Select</option><?php foreach($projects as $p) echo "<option value='{$p->id}' ".selected($inv->project_id ?? 0, $p->id, false).">{$p->title}</option>"; ?></select></div>
            </div>
            <div class="studiofy-form-row">
                <div class="studiofy-col"><label for="inv_issue">Issue Date</label><input type="date" name="issue_date" id="inv_issue" class="widefat" value="<?php echo $inv->issue_date ?? date('Y-m-d'); ?>"></div>
                <div class="studiofy-col"><label for="inv_due">Due Date</label><input type="date" name="due_date" id="inv_due" class="widefat" value="<?php echo $inv->due_date ?? date('Y-m-d', strtotime('+30 days')); ?>"></div>
            </div>
        </div>

        <div class="studiofy-panel">
            <h3>Line Items</h3>
            <div style="margin-bottom:10px;">
                <label for="item_select" class="screen-reader-text">Add from Library</label>
                <select id="item_select" style="max-width:200px;">
                    <option value="">+ Add from Library</option>
                    <?php foreach($saved_items as $si) echo "<option value='{$si->id}' data-rate='{$si->rate}' data-desc='".esc_attr($si->description)."'>".esc_html($si->title)."</option>"; ?>
                </select>
                <button type="button" class="button" id="add-item-btn">+ Add Blank Row</button>
            </div>

            <table class="widefat fixed striped">
                <thead><tr><th>Description</th><th width="10%">Qty</th><th width="15%">Rate</th><th width="15%">Amount</th><th width="5%"></th></tr></thead>
                <tbody id="line-items-body">
                    <?php if(!empty($line_items)) foreach($line_items as $idx => $item): ?>
                    <tr>
                        <td><input type="text" name="items[<?php echo $idx; ?>][desc]" class="widefat item-desc" value="<?php echo esc_attr($item['desc']); ?>"></td>
                        <td><input type="number" name="items[<?php echo $idx; ?>][qty]" class="widefat item-qty" value="<?php echo esc_attr($item['qty']); ?>"></td>
                        <td><input type="number" step="0.01" name="items[<?php echo $idx; ?>][rate]" class="widefat item-rate" value="<?php echo esc_attr($item['rate']); ?>"></td>
                        <td><span class="item-total">$<?php echo number_format($item['qty']*$item['rate'], 2); ?></span></td>
                        <td><button type="button" class="button-link-delete remove-row">&times;</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="text-align:right; padding:10px; font-size:16px;">
                <label for="tax-rate">Tax Rate (%):</label> 
                <input type="number" name="tax_rate" id="tax-rate" step="0.01" value="<?php echo $tax_rate; ?>" style="width:70px;">
                <br>
                <strong>Total: $<span id="invoice-total"><?php echo number_format($subtotal + $tax_amount, 2); ?></span></strong>
            </div>
        </div>

        <p class="submit"><button type="submit" class="button button-primary button-large">Save Invoice</button></p>
    </form>
</div>

<script>
jQuery(document).ready(function($){
    let rowIdx = <?php echo count($line_items); ?>;

    function addRow(desc = '', qty = 1, rate = 0) {
        let html = `<tr>
            <td><input type="text" name="items[${rowIdx}][desc]" class="widefat item-desc" value="${desc}"></td>
            <td><input type="number" name="items[${rowIdx}][qty]" class="widefat item-qty" value="${qty}"></td>
            <td><input type="number" step="0.01" name="items[${rowIdx}][rate]" class="widefat item-rate" value="${rate}"></td>
            <td><span class="item-total">$0.00</span></td>
            <td><button type="button" class="button-link-delete remove-row">&times;</button></td>
        </tr>`;
        $('#line-items-body').append(html);
        rowIdx++;
        calcTotal();
    }

    $('#add-item-btn').click(function(){ addRow(); });

    $('#item_select').change(function(){
        let opt = $(this).find(':selected');
        if(opt.val() !== '') {
            addRow(opt.text() + ' - ' + opt.data('desc'), 1, opt.data('rate'));
            $(this).val('');
        }
    });

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
        let tax = parseFloat($('#tax-rate').val()) || 0;
        let total = sub + (sub * (tax/100));
        $('#invoice-total').text(total.toFixed(2));
    }
});
</script>
