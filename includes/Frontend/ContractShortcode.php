<?php
/**
 * Contract Shortcode
 * @package Studiofy\Frontend
 * @version 2.2.28
 */

declare(strict_types=1);

namespace Studiofy\Frontend;

use function Studiofy\studiofy_get_asset_version;

class ContractShortcode {

    public function init(): void {
        add_shortcode('studiofy_contract_portal', [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_studiofy_client_sign_contract', [$this, 'handle_signature']);
        add_action('wp_ajax_nopriv_studiofy_client_sign_contract', [$this, 'handle_signature']);
    }

    public function enqueue_assets(): void {
        wp_register_style('studiofy-contract-front', STUDIOFY_URL . 'assets/css/contract-front.css', [], studiofy_get_asset_version('assets/css/contract-front.css'));
        wp_register_script('studiofy-signature-pad', STUDIOFY_URL . 'assets/js/signature-pad.js', [], '2.3.2', true);
        wp_register_script('studiofy-contract-front', STUDIOFY_URL . 'assets/js/contract-front.js', ['jquery', 'studiofy-signature-pad'], studiofy_get_asset_version('assets/js/contract-front.js'), true);
        
        wp_localize_script('studiofy-contract-front', 'studiofyContractSettings', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('studiofy_client_sign')
        ]);
    }

    public function render($atts): string {
        $contract_id = isset($_GET['contract_id']) ? (int)$_GET['contract_id'] : 0;
        
        if (!$contract_id) return '<p>No contract specified.</p>';

        global $wpdb;
        $contract = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_contracts WHERE id = %d", $contract_id));

        if (!$contract) return '<p>Contract not found.</p>';

        // Get Content
        $content = $contract->body_content;
        if ($contract->linked_post_id && class_exists('\Elementor\Plugin')) {
            $elem_content = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($contract->linked_post_id);
            if (!empty($elem_content)) $content = $elem_content;
        }

        wp_enqueue_style('studiofy-contract-front');
        wp_enqueue_script('studiofy-contract-front');

        ob_start();
        ?>
        <div class="studiofy-contract-container">
            <div class="contract-header">
                <h2><?php echo esc_html($contract->title); ?></h2>
                <div class="contract-meta">
                    <span>Date: <?php echo esc_html($contract->start_date); ?></span>
                    <span>Amount: $<?php echo number_format((float)$contract->amount, 2); ?></span>
                </div>
            </div>

            <div class="contract-body">
                <?php echo $content; ?>
            </div>

            <div class="contract-footer">
                <?php if ($contract->status === 'signed'): ?>
                    <div class="signed-status">
                        <h3>Signed Successfully</h3>
                        <p>Signed by: <strong><?php echo esc_html($contract->signed_name); ?></strong></p>
                        <p>Date: <?php echo esc_html($contract->signed_at); ?></p>
                        <img src="<?php echo esc_url($contract->signature_data); ?>" alt="Signature" class="signature-img">
                    </div>
                <?php else: ?>
                    <div class="signature-area">
                        <h3>Sign Contract</h3>
                        <p>By signing below, you agree to the terms listed above.</p>
                        
                        <form id="studiofy-sign-form">
                            <input type="hidden" name="contract_id" value="<?php echo $contract->id; ?>">
                            
                            <div class="form-group">
                                <label for="signed_name">Full Name</label>
                                <input type="text" id="signed_name" name="signed_name" required placeholder="Type your full name">
                            </div>

                            <div class="sig-pad-wrapper">
                                <canvas id="signature-canvas" width="600" height="200"></canvas>
                                <button type="button" id="clear-signature" class="button">Clear</button>
                            </div>
                            
                            <button type="submit" class="button button-primary button-large" id="btn-submit-sign">Submit Signature</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_signature(): void {
        check_ajax_referer('studiofy_client_sign', 'nonce');
        
        $id = (int) $_POST['contract_id'];
        $name = sanitize_text_field($_POST['signed_name']);
        $sig_data = $_POST['signature_data'];

        if (empty($sig_data) || empty($name)) wp_send_json_error('Missing signature data.');

        global $wpdb;
        $wpdb->update($wpdb->prefix . 'studiofy_contracts', [
            'signature_data' => $sig_data,
            'signed_name' => $name,
            'signed_at' => current_time('mysql'),
            'status' => 'signed'
        ], ['id' => $id]);

        wp_send_json_success(['message' => 'Contract signed successfully!']);
    }
}
