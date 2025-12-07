<?php
/**
 * Signature Shortcode
 * @package Studiofy\Frontend
 * @version 2.3.9
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
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'studiofy_signature')) {
            wp_enqueue_script('signature_pad', 'https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js', [], '4.0.0', true);
        }
    }

    public function render($atts): string {
        // Secure Token Lookup
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
        // Legacy fallback
        $id = isset($_GET['bid']) ? (int)$_GET['bid'] : 0;
        
        global $wpdb;
        if ($token) {
            $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_invoices WHERE access_token = %s", $token));
        } elseif ($id) {
            $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_invoices WHERE id = %d", $id));
        } else {
            return '<div class="studiofy-error">Invalid contract link.</div>';
        }

        if (!$record) return '<div class="studiofy-error">Contract not found.</div>';
        
        $is_signed = ($record->contract_status === 'Signed');
        $balance = (float)$record->amount; // Needs adjustment if partial payments tracked, but using total for now
        if ($record->status === 'Paid') $balance = 0.00;

        ob_start();
        ?>
        <style>
            .studiofy-contract-wrap { max-width: 800px; margin: 20px auto; font-family: Helvetica, Arial, sans-serif; }
            .contract-paper { background: #fff; padding: 40px; border: 1px solid #ddd; box-shadow: 0 5px 15px rgba(0,0,0,0.05); position: relative; }
            .contract-header { border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; text-align: center; }
            .contract-body { line-height: 1.6; color: #333; margin-bottom: 40px; }
            .signature-area { background: #f9f9f9; padding: 20px; border-radius: 8px; text-align: center; margin-top: 20px; }
            .balance-banner { background: #e3f2fd; color: #0d47a1; padding: 15px; text-align: center; font-weight: bold; margin-bottom: 20px; border-radius: 4px; }
            .btn-sign { background: #2271b1; color: #fff; padding: 12px 25px; border: none; font-size: 16px; cursor: pointer; border-radius: 4px; }
            .btn-sign:hover { background: #135e96; }
            .payment-methods { display: flex; gap: 10px; justify-content: center; margin-top: 15px; flex-wrap: wrap; }
            .pm-badge { background: #fff; border: 1px solid #ccc; padding: 8px 15px; border-radius: 20px; font-size: 13px; color: #555; }
            /* Modal */
            .sig-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center; }
            .sig-content { background: #fff; padding: 20px; border-radius: 8px; width: 500px; max-width: 95%; text-align: center; }
            canvas { border: 1px dashed #ccc; background: #fff; cursor: crosshair; }
        </style>

        <div class="studiofy-contract-wrap">
            <?php if($balance > 0): ?>
                <div class="balance-banner">Remaining Balance Due: $<?php echo number_format($balance, 2); ?></div>
            <?php else: ?>
                <div class="balance-banner" style="background:#f0fdf4; color:#166534;">No Payment Needed At This Time</div>
            <?php endif; ?>

            <div class="contract-paper">
                <div class="contract-header">
                    <h1><?php echo esc_html($record->title); ?></h1>
                    <p>Total Value: <?php echo esc_html($record->currency . ' ' . number_format((float)$record->amount, 2)); ?></p>
                </div>
                
                <div class="contract-body">
                    <?php echo wp_kses_post($record->contract_body); ?>
                </div>

                <?php if ($is_signed): ?>
                    <div class="signature-area" style="border: 2px solid #46b450; background: #f0fdf4;">
                        <h3>Signed Digitally</h3>
                        <p><strong>By:</strong> <?php echo esc_html($record->signed_name); ?></p>
                        <p><strong>On:</strong> <?php echo esc_html($record->signed_at); ?></p>
                        <p><strong>Ref:</strong> <?php echo esc_html($record->signature_serial); ?></p>
                    </div>
                <?php else: ?>
                    <div class="signature-area">
                        <p>Please review the terms above. By clicking below, you agree to sign this contract digitally.</p>
                        <button class="btn-sign" id="open-sign-modal">Sign Contract</button>
                    </div>
                <?php endif; ?>

                <?php 
                // Show Payment Options if Balance > 0
                if ($balance > 0): 
                    $methods = json_decode($record->payment_methods, true) ?: [];
                ?>
                    <div style="margin-top: 30px; text-align: center; border-top: 1px solid #eee; padding-top: 20px;">
                        <h4>Payment Options</h4>
                        <?php if (!empty($methods)): ?>
                            <div class="payment-methods">
                                <?php foreach($methods as $m) echo "<span class='pm-badge'>$m</span>"; ?>
                            </div>
                            <p style="font-size:12px; color:#777; margin-top:10px;">Secure payment link will appear after signing.</p>
                        <?php else: ?>
                            <p style="font-size:12px; color:#777;">Please contact the studio for payment instructions.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$is_signed): ?>
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
                    id: <?php echo $record->id; ?>,
                    data: signaturePad.toDataURL(),
                    name: name,
                    nonce: '<?php echo wp_create_nonce("studiofy_sign_" . $record->id); ?>'
                };

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
                        // Redirect Logic
                        <?php if ($balance <= 0): ?>
                            window.location.href = '<?php echo home_url(); ?>';
                        <?php else: ?>
                            // Reload to show signed state, ideally redirect to payment page if integrated
                            location.reload();
                        <?php endif; ?>
                    } else {
                        alert('Error: ' + res.data);
                        btn.disabled = false;
                        btn.innerText = 'Adopt & Sign';
                    }
                });
            };
        });
        </script>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    public function handle_submit(): void {
        $id = (int)$_POST['id'];
        check_ajax_referer('studiofy_sign_' . $id, 'nonce');
        
        global $wpdb;
        $ip = $_SERVER['REMOTE_ADDR'];
        $timestamp = current_time('mysql');
        $serial = strtoupper(md5($ip . $timestamp . rand()));
        
        $wpdb->update($wpdb->prefix . 'studiofy_invoices', [
            'contract_status' => 'Signed',
            'signature_data' => $_POST['data'],
            'signed_name' => sanitize_text_field($_POST['name']),
            'signed_ip' => $ip,
            'signed_at' => $timestamp,
            'signature_serial' => $serial
        ], ['id' => $id]);

        $admin_email = get_option('admin_email');
        wp_mail($admin_email, 'Contract Signed: #' . $id, "A contract has been signed by {$_POST['name']}.\nSerial: $serial");

        wp_send_json_success();
    }
}
