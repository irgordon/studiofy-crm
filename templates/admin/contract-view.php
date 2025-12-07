<div class="wrap">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <h1><?php echo esc_html($contract->title); ?></h1>
        <a href="<?php echo admin_url('admin.php?page=studiofy-contracts'); ?>" class="button">&laquo; Back to Contracts</a>
    </div>

    <?php if ($contract->status !== 'signed'): ?>
    <div class="notice notice-info inline" style="padding: 10px; margin-bottom: 20px; border-left-color: #2271b1;">
        <p><strong>Client Signing Link:</strong></p>
        <?php if (!empty($signing_link)): ?>
            <input type="text" value="<?php echo esc_url($signing_link); ?>" class="widefat" readonly onclick="this.select()">
            <p class="description">Send this URL to your client. Requires a page with the <code>[studiofy_contract_portal]</code> shortcode.</p>
        <?php else: ?>
            <p><em>No 'Contract Portal' page found. Create a page with the shortcode <code>[studiofy_contract_portal]</code> to generate a signing link.</em></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="studiofy-contract-paper-container">
        <div class="studiofy-contract-paper">
            <div class="paper-header">
                <h2><?php echo esc_html($contract->title); ?></h2>
                <div class="meta">
                    <span><strong>Date:</strong> <?php echo esc_html($contract->start_date); ?></span>
                    <span><strong>Amount:</strong> $<?php echo number_format((float)$contract->amount, 2); ?></span>
                </div>
                <hr>
            </div>

            <div class="paper-body">
                <?php echo $view_content; ?>
            </div>
            
            <div class="paper-footer">
                <hr>
                <h3>Signatures</h3>
                <?php if ($contract->status === 'signed'): ?>
                    <div class="signed-box">
                        <img src="<?php echo esc_url($contract->signature_data); ?>" style="max-width:250px;">
                        <p>Signed by: <strong><?php echo esc_html($contract->signed_name); ?></strong><br>
                        Date: <?php echo esc_html($contract->signed_at); ?></p>
                    </div>
                <?php else: ?>
                    <div class="unsigned-box">
                        <p class="status-pending">Status: Pending Client Signature</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
