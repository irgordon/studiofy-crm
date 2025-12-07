<?php
/**
 * Billing Builder (Unified)
 * @version 2.3.3
 */
?>
<div class="wrap studiofy-billing-wrap">
    <h1><?php echo $data->id ? 'Edit Billing Record' : 'New Billing Record'; ?></h1>
    
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="billing-form">
        <input type="hidden" name="action" value="studiofy_save_billing">
        <?php wp_nonce_field('save_billing', 'studiofy_nonce'); ?>
        <?php if ($data->id) echo '<input type="hidden" name="id" value="' . $data->id . '">'; ?>

        <div class="studiofy-panel">
            <div class="studiofy-form-row">
                <div class="studiofy-col">
                    <label>Contract Title</label>
                    <input type="text" name="title" value="<?php echo esc_attr($data->title); ?>" class="widefat" required placeholder="e.g. Wedding Agreement">
                </div>
                <div class="studiofy-col">
                    <label>Client</label>
                    <select name="customer_id" class="widefat" required>
                        <option value="">Select Client</option>
                        <?php foreach($customers as $c) echo "<option value='{$c->id}' ".selected($data->customer_id, $c->id, false).">{$c->first_name} {$c->last_name}</option>"; ?>
                    </select>
                </div>
                <div class="studiofy-col">
                    <label>Service Type</label>
                    <input type="text" name="service_type" list="service_options" value="<?php echo esc_attr($data->service_type); ?>" class="widefat" placeholder="Type or select...">
                    <datalist id="service_options">
                        <option value="Portrait">
                        <option value="Wedding">
                        <option value="Event">
                        <option value="Commercial">
                        <option value="Lifestyle">
                    </datalist>
                </div>
                <div class="studiofy-col">
                    <label>Payment Status</label>
                    <select name="payment_status" class="widefat">
                        <option <?php selected($data->status, 'Draft'); ?>>Draft</option>
                        <option <?php selected($data->status, 'Unpaid'); ?>>Unpaid</option>
                        <option <?php selected($data->status, 'Paid'); ?>>Paid In Full</option>
                        <option <?php selected($data->status, 'ProBono'); ?>>ProBono</option>
                    </select>
                </div>
                 <div class="studiofy-col">
                    <label>Contract Status</label>
                    <select name="contract_status" class="widefat">
                        <option <?php selected($data->contract_status, 'Unsigned'); ?>>Unsigned</option>
                        <option <?php selected($data->contract_status, 'Signed'); ?>>Signed</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="studiofy-panel">
            <h3>Contract Terms</h3>
            <?php 
            wp_editor($data->contract_body, 'contract_body', [
                'textarea_name' => 'contract_body',
                'media_buttons' => false,
                'textarea_rows' => 15,
                'teeny' => true
            ]); 
            ?>
        </div>

        <div class="studiofy-panel">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h3>Items</h3>
                <div class="dropdown-wrapper">
                    <button type="button" class="button" id="toggle-options">Options ▼</button>
                    <div class="dropdown-menu" id="options-menu" style="display:none;">
                        <label><input type="checkbox" id="opt-deposit" <?php checked($data->deposit_amount > 0); ?>> Add Deposit</label>
                        <label><input type="checkbox" id="opt-discount"> Add Discount</label>
                        
                        <hr style="margin:5px 0;">
                        <strong style="display:block; margin-bottom:5px; font-size:11px;">Tipping / Fees</strong>
                        <label><input type="radio" name="tip_option" value="0" checked> None</label>
                        <label><input type="radio" name="tip_option" value="5"> 5% Tip</label>
                        <label><input type="radio" name="tip_option" value="10"> 10% Tip</label>
                        <label><input type="radio" name="tip_option" value="20"> 20% Tip</label>
                        <label><input type="checkbox" name="apply_service_fee" id="opt-service-fee" value="1" <?php checked($data->service_fee > 0); ?>> Service Fee (3%)</label>
                        
                        <hr style="margin:5px 0;">
                        <label><input type="checkbox" name="make_recurring"> Make Recurring</label>
                    </div>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th width="50%">Item Name</th><th width="20%">Rate</th><th width="10%">QTY</th><th width="20%">Total</th><th></th></tr></thead>
                <tbody id="billing-items-body">
                    <?php foreach($line_items as $idx => $item): ?>
                    <tr>
                        <td><input type="text" name="items[<?php echo $idx; ?>][name]" value="<?php echo esc_attr($item['desc']); ?>" class="widefat"></td>
                        <td><input type="number" step="0.01" name="items[<?php echo $idx; ?>][rate]" value="<?php echo esc_attr($item['rate']); ?>" class="widefat item-rate"></td>
                        <td><input type="number" name="items[<?php echo $idx; ?>][qty]" value="<?php echo esc_attr($item['qty']); ?>" class="widefat item-qty"></td>
                        <td><span class="item-total">$0.00</span></td>
                        <td><button type="button" class="button-link-delete remove-row">&times;</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><button type="button" class="button" id="add-item-row">+ Add Item</button></p>
            
            <div class="billing-totals">
                <div class="total-row"><span>Subtotal:</span> <span id="disp-subtotal">0.00</span></div>
                
                <div id="discount-row" style="display:none;" class="total-row">
                    <span>Discount (%): <input type="number" step="1" name="discount_percent" id="discount_percent" value="0" style="width:60px;"></span> 
                    <span id="disp-discount" style="color:red;">-0.00</span>
                </div>

                <div class="total-row"><span>Tax (6%):</span> <span id="disp-tax">0.00</span><input type="hidden" name="tax_rate" value="6"></div>
                
                <div id="service-fee-row" style="display:none;" class="total-row"><span>Service Fee (3%):</span> <span id="disp-service-fee">0.00</span></div>
                <div id="tip-row" style="display:none;" class="total-row"><span>Tip:</span> <span id="disp-tip">0.00</span><input type="hidden" name="tip_amount" id="input_tip_amount" value="0"></div>

                <div id="deposit-row" style="display:none; border-top:1px dashed #ccc; padding-top:10px; margin-top:10px;">
                    <label>Deposit Amount: $<input type="number" step="0.01" name="deposit_amount" value="<?php echo esc_attr($data->deposit_amount); ?>" style="width:100px;"></label>
                    <label style="margin-left:10px;">Final Due: <input type="date" name="final_due_date" value="<?php echo esc_attr($data->due_date); ?>"></label>
                </div>

                <div class="total-row final"><span>Total:</span> <span id="disp-total">0.00</span></div>
            </div>
        </div>

        <div class="studiofy-panel">
            <h3>Payment Method</h3>
            <div class="payment-methods-grid">
                <?php 
                $methods = ['Square', 'Stripe', 'PayPal', 'CashApp', 'Bank Transfer', 'Cash or Check'];
                foreach($methods as $m) {
                    $checked = in_array($m, $active_methods) ? 'checked' : '';
                    echo "<label class='pm-btn'><input type='checkbox' name='payment_methods[]' value='$m' $checked> $m</label>";
                }
                ?>
            </div>
            
            <div style="margin-top:20px;">
                <label>Memo</label>
                <textarea name="memo" class="widefat" rows="2"><?php echo esc_textarea($data->memo); ?></textarea>
            </div>
        </div>

        <div class="studiofy-actions-bar">
            <a href="<?php echo admin_url('admin.php?page=studiofy-billing'); ?>" class="button button-large">Close window</a>
            <button type="submit" class="button button-primary button-large">Create / Update</button>
        </div>
    </form>
</div>
