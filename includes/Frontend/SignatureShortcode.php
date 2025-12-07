<?php
/**
 * Signature Shortcode
 * @package Studiofy\Frontend
 * @version 2.3.5
 */

declare(strict_types=1);

namespace Studiofy\Frontend;

use Studiofy\Security\Encryption;

class SignatureShortcode {

    public function init(): void {
        add_shortcode('studiofy_signature', [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_studiofy_submit_signature', [$this, 'handle_submit']);
        add_action('wp_ajax_nopriv_studiofy_submit_signature', [$this, 'handle_submit']);
    }

    public function enqueue_scripts(): void {
        // Enqueue only on signature page or check post content
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'studiofy_signature')) {
            wp_enqueue_script('signature_pad', 'https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js', [], '4.0.0', true);
        }
    }

    public function render($atts): string {
        $id = isset($_GET['bid']) ? (int)$_GET['bid'] : 0;
        
        if (!$id) return '<div class="studiofy-error">Invalid contract link.</div>';

        global $wpdb;
        $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_invoices WHERE id = %d", $id));

        if (!$record) return '<div class="studiofy-error">Contract not found.</div>';
        if ($record->contract_status === 'Signed') return '<div class="studiofy-success">This contract has already been signed. Thank you!</div>';

        // Load styles inline for simplicity in this response
        ob_start();
        ?>
        <style>
            .studiofy-contract-wrap { max-width: 800px; margin: 20px auto; font-family: Helvetica, Arial, sans-serif; }
            .contract-paper { background: #fff; padding: 40px; border: 1px solid #ddd; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
            .contract-header { border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; text-align: center; }
            .contract-body { line-height: 1.6; color: #333; margin-bottom: 40px; }
            .signature-area { background: #f9f9f9; padding: 20px; border-radius: 8px; text-align: center; }
            .btn-sign { background: #2271b1; color: #fff; padding: 12px 25px; border: none; font-size: 16px; cursor: pointer; border-radius: 4px; }
            .btn-sign:hover { background: #135e96; }
            
            /* Modal */
            .sig-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center; }
            .sig-content { background: #fff; padding: 20px; border-radius: 8px; width: 500px; max-width: 95%; text-align: center; }
            canvas { border: 1px dashed #ccc; background: #fff; cursor: crosshair; }
        </style>

        <div class="studiofy-contract-wrap">
            <div class="contract-paper">
                <div class="contract-header">
                    <h1><?php echo esc_html($record->title); ?></h1>
                    <p>Total Value: <?php echo esc_html($record->currency . ' ' . number_format((float)$record->amount, 2)); ?></p>
                </div>
                
                <div class="contract-body">
                    <?php echo wp_kses_post($record->contract_body); ?>
                </div>

                <div class="signature-area">
                    <p>Please review the terms above. By clicking below, you agree to sign this contract digitally.</p>
                    <button class="btn-sign" id="open-sign-modal">Sign Contract</button>
                </div>
            </div>
        </div>

        <div id="sig-modal" class="sig-modal">
            <div class="sig-content">
                <h3>Draw Your Signature</h3>
                <canvas id="sig-canvas" width="450" height="200"></canvas>
                <div style="margin: 15px 0;">
                    <label>Type Full Name: <input type="text" id="signer-name" style="padding:8px; width:200px;"></label>
                </div>
                <div style="display:flex; justify-content: space-between;">
                    <button id="clear-sig" style="background:#ddd; border:none; padding:8px 15px; cursor:pointer;">Clear</button>
                    <button id="close-sig" style="background:transparent; border:1px solid #ddd; padding:8px 15px; cursor:pointer;">Cancel</button>
                    <button id="submit-sig" class="btn-sign">Adopt & Sign</button>
                </div>
                <p id="sig-status" style="margin-top:10px; font-size:12px;"></p>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById('sig-modal');
            var canvas = document.getElementById('sig-canvas');
            var signaturePad = new SignaturePad(canvas);
            
            document.getElementById('open-sign-modal').onclick = function() { modal.style.display = 'flex'; signaturePad.clear(); };
            document.getElementById('close-sig').onclick = function() { modal.style.display = 'none'; };
            document.getElementById('clear-sig').onclick = function() { signaturePad.clear(); };

            document.getElementById('submit-sig').onclick = function() {
                if (signaturePad.isEmpty()) { alert('Please draw your signature.'); return; }
                var name = document.getElementById('signer-name').value;
                if (!name) { alert('Please type your full name.'); return; }

                var btn = this;
                btn.disabled = true;
                btn.innerText = 'Signing...';

                var data = {
                    action: 'studiofy_submit_signature',
                    id: <?php echo $id; ?>,
                    data: signaturePad.toDataURL(),
                    name: name,
                    nonce: '<?php echo wp_create_nonce("studiofy_sign_" . $id); ?>'
                };

                // Simple AJAX fetch
                var formData = new FormData();
                for (var key in data) formData.append(key, data[key]);

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(res => {
                    if (res.success) {
                        alert('Contract Signed Successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + res.data);
                        btn.disabled = false;
                        btn.innerText = 'Adopt & Sign';
                    }
                });
            };
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function handle_submit(): void {
        $id = (int)$_POST['id'];
        check_ajax_referer('studiofy_sign_' . $id, 'nonce');
        
        global $wpdb;
        $ip = $_SERVER['REMOTE_ADDR'];
        $timestamp = current_time('mysql');
        $serial = strtoupper(md5($ip . $timestamp . rand())); // Unique Serial
        
        $wpdb->update($wpdb->prefix . 'studiofy_invoices', [
            'contract_status' => 'Signed',
            'signature_data' => $_POST['data'], // Base64 Image
            'signed_name' => sanitize_text_field($_POST['name']),
            'signed_ip' => $ip,
            'signed_at' => $timestamp,
            'signature_serial' => $serial
        ], ['id' => $id]);

        // Email Admin
        $admin_email = get_option('admin_email');
        wp_mail($admin_email, 'Contract Signed: #' . $id, "A contract has been signed by {$_POST['name']}.\nSerial: $serial");

        wp_send_json_success();
    }
}
