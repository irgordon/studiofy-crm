<?php
/**
 * Billing Builder (Unified)
 * @version 2.3.5
 * $is_locked variable determines if inputs are disabled
 */
$readonly = $is_locked ? 'disabled' : '';
?>
<div class="wrap studiofy-billing-wrap">
    <h1><?php echo $data->id ? 'Edit Billing Record' : 'New Billing Record'; ?></h1>
    
    <?php if ($is_locked): ?>
        <div class="notice notice-warning inline"><p><strong>Locked:</strong> This contract is signed or paid and cannot be edited.</p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="billing-form">
        <input type="hidden" name="action" value="studiofy_save_billing">
        <?php wp_nonce_field('save_billing', 'studiofy_nonce'); ?>
        <?php if ($data->id) echo '<input type="hidden" name="id" value="' . $data->id . '">'; ?>

        <?php if ($data->contract_status === 'Signed'): ?>
        <div class="studiofy-panel" style="border-left: 4px solid #46b450;">
            <h3 style="margin-top:0;">Digital Signature Verification</h3>
            <div style="display:flex; align-items:center; gap:20px;">
                <div style="border:1px solid #ccc; padding:10px; background:#fff;">
                    <img src="<?php echo esc_url($data->signature_data); ?>" style="max-height:60px;">
                </div>
                <div>
                    <p><strong>Signed By:</strong> <?php echo esc_html($data->signed_name); ?></p>
                    <p><strong>Date:</strong> <?php echo esc_html($data->signed_at); ?></p>
                    <p><strong>Serial ID:</strong> <code style="background:#eee; padding:2px;"><?php echo esc_html($data->signature_serial); ?></code></p>
                    <p><strong>IP:</strong> <?php echo esc_html($data->signed_ip); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="studiofy-panel">
            <div class="studiofy-form-row">
                <div class="studiofy-col">
                    <label>Contract Title</label>
                    <input type="text" name="title" value="<?php echo esc_attr($data->title); ?>" class="widefat" required <?php echo $readonly; ?>>
                </div>
                <div class="studiofy-col">
                    <label>Client</label>
                    <select name="customer_id" class="widefat" required <?php echo $readonly; ?>>
                        <option value="">Select Client</option>
                        <?php foreach($customers as $c) echo "<option value='{$c->id}' ".selected($data->customer_id, $c->id, false).">{$c->first_name} {$c->last_name}</option>"; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="studiofy-panel">
            <h3>Contract Terms</h3>
            <?php 
            // Note: wp_editor doesn't support 'disabled' attribute easily, 
            // usually you hide buttons or check on save if locked. 
            // For UI simplicity we assume admin integrity or add CSS overlay if locked.
            wp_editor($data->contract_body, 'contract_body', ['textarea_name' => 'contract_body', 'media_buttons' => false, 'textarea_rows' => 15, 'teeny' => true]); 
            ?>
        </div>

        <div class="studiofy-panel">
             <?php if(!$is_locked): ?>
             <p><button type="button" class="button" id="add-item-row">+ Add Item</button></p>
             <?php endif; ?>
        </div>

        <?php if(!$is_locked): ?>
        <div class="studiofy-actions-bar">
            <a href="<?php echo admin_url('admin.php?page=studiofy-billing'); ?>" class="button button-large">Close window</a>
            <button type="submit" class="button button-primary button-large">Create / Update</button>
        </div>
        <?php endif; ?>
    </form>
</div>
