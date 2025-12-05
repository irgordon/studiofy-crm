<div class="wrap studiofy-dark-theme">
    <h1>New Invoice</h1>
    <form method="post" action="<?php echo admin_url('admin_post.php'); ?>">
        <input type="hidden" name="action" value="studiofy_save_invoice">
        <?php wp_nonce_field('save_invoice', 'studiofy_nonce'); ?>

        <div class="studiofy-panel">
            <div class="studiofy-form-row">
                <div class="studiofy-col"><label>Invoice Number</label><input type="text" name="invoice_number" value="<?php echo esc_attr($inv_num); ?>" readonly class="widefat"></div>
                <div class="studiofy-col"><label>Status</label>
                    <select name="status" class="widefat">
                        <option value="Draft">Draft</option><option value="Sent">Sent</option><option value="Paid">Paid</option>
                    </select>
                </div>
            </div>
            <div class="studiofy-form-row">
                <div class="studiofy-col"><label>Client *</label>
                    <select name="client_id" required class="widefat">
                        <option value="">Select a client</option>
                        <?php foreach($clients as $c) echo "<option value='{$c->id}'>{$c->first_name} {$c->last_name}</option>"; ?>
                    </select>
                </div>
                <div class="studiofy-col"><label>Project</label>
                    <select name="project_id" class="widefat">
                        <option value="">Select a project</option>
                        <?php foreach($projects as $p) echo "<option value='{$p->id}'>{$p->title}</option>"; ?>
                    </select>
                </div>
            </div>
            <div class="studiofy-form-row">
                <div class="studiofy-col"><label>Issue Date *</label><input type="date" name="issue_date" required class="widefat" value="<?php echo date('Y-m-d'); ?>"></div>
                <div class="studiofy-col"><label>Due Date *</label><input type="date" name="due_date" required class="widefat"></div>
            </div>
        </div>

        <div class="studiofy-panel" style="margin-top:20px;">
            <h3>Line Items <button type="button" class="button" id="add-item" style="float:right;">+ Add Item</button></h3>
            <table class="widefat fixed striped">
                <thead><tr><th>Description</th><th width="10%">Qty</th><th width="15%">Rate</th><th width="15%">Amount</th><th width="5%"></th></tr></thead>
                <tbody id="line-items-body"></tbody>
            </table>
            <div style="text-align:right; padding:10px; font-size:16px;">
                Tax: $<input type="number" name="tax_amount" id="tax-input" step="0.01" value="0.00" style="width:100px;"><br>
                <strong>Total: $<span id="invoice-total">0.00</span></strong>
            </div>
        </div>

        <p class="submit"><button type="submit" class="button button-primary button-large">Create Invoice</button></p>
    </form>
</div>

<script>
jQuery(document).ready(function($){
    let idx = 0;
    function addRow() {
        const row = `<tr>
            <td><input type="text" name="items[${idx}][desc]" class="widefat" placeholder="Item description"></td>
            <td><input type="number" name="items[${idx}][qty]" class="widefat qty" value="1"></td>
            <td><input type="number" step="0.01" name="items[${idx}][rate]" class="widefat rate" value="0.00"></td>
            <td><input type="text" name="items[${idx}][amount]" class="widefat amount" readonly value="0.00"></td>
            <td><span class="dashicons dashicons-trash remove-row" style="cursor:pointer; color:red; margin-top:5px;"></span></td>
        </tr>`;
        $('#line-items-body').append(row);
        idx++;
        calcTotal();
    }
    
    $('#add-item').click(addRow);
    $(document).on('click', '.remove-row', function(){ $(this).closest('tr').remove(); calcTotal(); });
    $(document).on('input', '.qty, .rate, #tax-input', function(){
        const row = $(this).closest('tr');
        const qty = parseFloat(row.find('.qty').val()) || 0;
        const rate = parseFloat(row.find('.rate').val()) || 0;
        const amt = qty * rate;
        row.find('.amount').val(amt.toFixed(2));
        calcTotal();
    });

    function calcTotal() {
        let subtotal = 0;
        $('.amount').each(function(){ subtotal += parseFloat($(this).val()) || 0; });
        const tax = parseFloat($('#tax-input').val()) || 0;
        $('#invoice-total').text((subtotal + tax).toFixed(2));
    }
    
    addRow(); 
});
</script>
