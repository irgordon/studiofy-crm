<div class="wrap">
    <h1>Generate Invoice</h1>
    <form method="post" action="admin-post.php" class="studiofy-form-grid">
        <input type="hidden" name="action" value="studiofy_save_invoice">
        <?php wp_nonce_field('save_invoice'); ?>

        <div class="postbox">
            <div class="inside">
                <div class="form-row">
                    <label>Invoice Number</label>
                    <input type="text" name="invoice_number" value="<?php echo esc_attr($next_num); ?>" readonly>
                </div>
                <div class="form-row">
                    <label>Recipient Name</label>
                    <input type="text" name="r_name" required>
                </div>
                <div class="form-row">
                    <label>Amount ($)</label>
                    <input type="number" step="0.01" name="amount" required>
                </div>
                <div class="form-row">
                    <label>Tax (%)</label>
                    <input type="number" step="0.01" name="tax_rate" value="0.00">
                </div>
                <div class="form-row">
                    <label>Payment Type</label>
                    <select name="payment_type">
                        <option>Square</option><option>Cash</option><option>Zelle</option>
                    </select>
                </div>
                <button type="submit" class="button button-primary">Save Invoice</button>
            </div>
        </div>
    </form>
</div>
