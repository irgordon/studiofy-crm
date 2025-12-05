<?php
/**
 * Invoice API Routes
 * @package Studiofy\Api
 * @version 2.0.5
 */

declare(strict_types=1);

namespace Studiofy\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Studiofy\Gateways\SquareGateway;
use Studiofy\Utils\SchemaValidator;
use Studiofy\Export\IcalService;

class InvoiceRoutes {

    public function init(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        $namespace = 'studiofy/v1';
        register_rest_route($namespace, '/invoices', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'create_invoice'],
            'permission_callback' => fn() => current_user_can('manage_options')
        ]);
        register_rest_route($namespace, '/invoices/(?P<id>\d+)/ical', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'download_ical'],
            'permission_callback' => '__return_true'
        ]);
    }

    public function create_invoice(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $params = $request->get_json_params();
        
        // Update schema to look for customer_id
        $schema = ['project_id' => 'int', 'customer_id' => 'int', 'title' => 'string', 'amount' => 'float', 'due_date' => 'date', 'currency' => 'string'];
        $valid_data = SchemaValidator::validate($params, $schema);

        $gateway = new SquareGateway();
        $square_result = $gateway->create_invoice($valid_data);

        if (is_wp_error($square_result)) {
            return new WP_REST_Response(['success' => false, 'message' => $square_result->get_error_message()], 400);
        }

        $table = $wpdb->prefix . 'studiofy_invoices';
        $wpdb->insert($table, [
            'project_id' => $valid_data['project_id'],
            'customer_id' => $valid_data['customer_id'], // Refactored
            'external_id' => $square_result['id'],
            'order_id' => $square_result['order_id'],
            'title' => $valid_data['title'],
            'amount' => $valid_data['amount'],
            'currency' => $valid_data['currency'] ?? 'USD',
            'due_date' => $valid_data['due_date'],
            'status' => $square_result['status'],
            'payment_link' => $square_result['payment_link']
        ]);

        return new WP_REST_Response(['success' => true, 'id' => $wpdb->insert_id], 200);
    }

    public function download_ical(WP_REST_Request $request): void {
        global $wpdb;
        $id = (int) $request->get_param('id');
        $invoice = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_invoices WHERE id = %d", $id));
        if (!$invoice) { status_header(404); die('Invoice not found'); }

        $ical = new IcalService();
        $content = $ical->generate_event(
            "Payment Due: " . $invoice->title,
            "Amount: {$invoice->amount} {$invoice->currency}. Link: {$invoice->payment_link}",
            $invoice->due_date,
            "inv_" . $invoice->id
        );
        $ical->download("invoice_{$id}", $content);
    }
}
