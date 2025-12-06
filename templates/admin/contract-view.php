<div class="wrap">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h1><?php echo esc_html($contract->title); ?></h1>
        <a href="<?php echo admin_url('admin.php?page=studiofy-contracts'); ?>" class="button">&laquo; Back to Contracts</a>
    </div>
    
    <div class="studiofy-contract-paper">
        <?php echo $view_content; // Rendered Elementor Content ?>
        
        <div class="signature-section">
            <h3>Signatures</h3>
            <?php if ($contract->status === 'signed'): ?>
                <div class="signed-box">
                    <img src="<?php echo esc_url($contract->signature_data); ?>" style="max-width:300px;">
                    <p>Signed by: <strong><?php echo esc_html($contract->signed_name); ?></strong><br>
                    Date: <?php echo esc_html($contract->signed_at); ?></p>
                </div>
            <?php else: ?>
                <p>Status: <span class="studiofy-badge draft">Pending Signature</span></p>
                <p><em>(Client signature capture is available on the frontend portal)</em></p>
            <?php endif; ?>
        </div>
    </div>
</div>
