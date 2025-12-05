<div class="wrap studiofy-contract-viewer">
    <div class="studiofy-paper">
        <div class="contract-header">
            <h1>CONTRACT AGREEMENT</h1>
            <p><strong>Reference:</strong> #<?php echo $contract->id; ?></p>
            <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($contract->created_at)); ?></p>
        </div>

        <hr>

        <div class="contract-body">
            <?php echo wp_kses_post($contract->body_content); ?>
        </div>

        <hr>

        <div class="contract-footer">
            <?php if ($contract->status === 'signed'): ?>
                <div class="signature-stamp">
                    <p>Digitally Signed by: <strong><?php echo esc_html($contract->signed_name); ?></strong></p>
                    <p>Date: <?php echo $contract->signed_at; ?></p>
                    <div class="signature-image">
                        <img src="<?php echo $contract->signature_data; ?>" alt="Signature">
                    </div>
                </div>
            <?php else: ?>
                <div class="signing-area no-print">
                    <h3>Sign Here</h3>
                    <canvas id="studiofy-signature-pad" width="500" height="200"></canvas>
                    <div class="controls">
                        <button type="button" class="button" id="clear-signature">Clear</button>
                    </div>
                    
                    <form method="post" action="<?php echo admin_url('admin_post.php'); ?>" id="signature-form">
                        <input type="hidden" name="action" value="studiofy_sign_contract">
                        <input type="hidden" name="contract_id" value="<?php echo $contract->id; ?>">
                        <input type="hidden" name="signature_data" id="signature-data">
                        
                        <p>
                            <label>Type Full Name to Confirm:</label><br>
                            <input type="text" name="signed_name" required class="regular-text">
                        </p>
                        <button type="submit" class="button button-primary button-large">Accept & Sign Contract</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
