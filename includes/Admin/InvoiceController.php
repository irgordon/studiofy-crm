<?php
/**
 * Invoice Admin UI
 * @package Studiofy\Admin
 * @version 2.0.1
 */

declare(strict_types=1);

namespace Studiofy\Admin;

class InvoiceController {

    public function init(): void {}

    public function render_page(): void {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}studiofy_invoices ORDER BY created_at DESC");

        echo '<div class="wrap"><h1>Invoices</h1>';
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Title</th><th>Amount</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $ical = rest_url("studiofy/v1/invoices/{$r->id}/ical");
            echo "<tr><td>{$r->title}</td><td>{$r->amount} {$r->currency}</td><td>{$r->status}</td><td><a href='{$r->payment_link}' target='_blank'>Pay</a> | <a href='{$ical}'>iCal</a></td></tr>";
        }
        echo '</tbody></table></div>';
    }
}
