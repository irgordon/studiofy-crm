<?php
/**
 * Payment Shortcode
 * @package Studiofy\Frontend
 * @version 2.2.56
 */

declare(strict_types=1);

namespace Studiofy\Frontend;

use function Studiofy\studiofy_get_asset_version;

class PaymentShortcode {

    public function init(): void {
        add_shortcode('studiofy_payment', [$this, 'render']);
    }

    public function render($atts): string {
        $invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;
        
        if (!$invoice_id) {
            return '<div class="studiofy-payment-error">Invalid payment link. Please contact the studio.</div>';
        }

        global $wpdb;
        $invoice = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_invoices WHERE id = %d", $invoice_id));

        if (!$invoice) {
            return '<div class="studiofy-payment-error">Invoice not found.</div>';
        }

        if ($invoice->status === 'Paid') {
            return '<div class="studiofy-payment-success"><h3>Invoice Paid</h3><p>This invoice has already been paid. Thank you!</p></div>';
        }

        // Load Branding
        $options = (array) get_option('studiofy_branding', []);
        $business_name = !empty($options['business_name']) ? (string)$options['business_name'] : 'Photography Studio';
        $amount = number_format((float)$invoice->amount, 2);

        ob_start();
        ?>
        <div class="studiofy-payment-container">
            <div class="payment-header">
                <h2>Pay Invoice: <?php echo esc_html($invoice->invoice_number); ?></h2>
                <p><strong>To:</strong> <?php echo esc_html($business_name); ?></p>
            </div>
            
            <div class="payment-summary">
                <div class="summary-row">
                    <span>Description</span>
                    <span>Total</span>
                </div>
                <div class="summary-row total">
                    <span><?php echo esc_html($invoice->title); ?></span>
                    <span>$<?php echo esc_html($amount); ?></span>
                </div>
            </div>

            <div class="studiofy-square-form">
                <div id="card-container"></div>
                <button id="card-button" type="button" class="button-pay">Pay $<?php echo esc_html($amount); ?></button>
                <p class="secure-notice"><span class="dashicons dashicons-lock"></span> Payments secured by Square</p>
            </div>
            
            <style>
                .studiofy-payment-container { max-width: 500px; margin: 40px auto; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); font-family: sans-serif; }
                .payment-header { text-align: center; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
                .payment-summary { background: #f9f9f9; padding: 15px; border-radius: 4px; margin-bottom: 30px; }
                .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
                .summary-row.total { font-weight: bold; font-size: 1.2em; margin-bottom: 0; border-top: 1px solid #ddd; padding-top: 10px; }
                .button-pay { width: 100%; background: #2271b1; color: white; padding: 15px; border: none; border-radius: 4px; font-size: 18px; cursor: pointer; transition: background 0.2s; }
                .button-pay:hover { background: #135e96; }
                .secure-notice { text-align: center; font-size: 12px; color: #777; margin-top: 15px; }
            </style>
        </div>
        <?php
        return ob_get_clean();
    }
}
