<?php
/**
 * Square Gateway
 * @package Studiofy\Gateways
 * @version 2.0.4
 */

declare(strict_types=1);

namespace Studiofy\Gateways;

use WP_Error;

class SquareGateway {

    private string $access_token;
    private string $base_url;
    private string $location_id;

    public function __construct() {
        $options = get_option('studiofy_branding');
        $this->access_token = $options['square_access_token'] ?? '';
        $this->location_id = $options['square_location_id'] ?? '';
        
        $env = $options['square_env'] ?? 'sandbox'; 
        
        if ($env === 'production') {
            $this->base_url = 'https://connect.squareup.com/v2';
        } else {
            $this->base_url = 'https://connect.squareupsandbox.com/v2';
        }
    }

    public function create_invoice(array $data): array|WP_Error {
        if (empty($this->access_token)) {
            return new WP_Error('auth_error', 'Square Access Token missing.');
        }

        // 1. Create Order
        $order_body = [
            'idempotency_key' => uniqid('ord_'),
            'order' => [
                'location_id' => $this->location_id,
                'line_items' => [[
                    'name' => $data['title'],
                    'quantity' => '1',
                    'base_price_money' => [
                        'amount' => (int) ($data['amount'] * 100),
                        'currency' => $data['currency']
                    ]
                ]]
            ]
        ];

        $order_res = $this->request('/orders', 'POST', $order_body);
        if (is_wp_error($order_res)) return $order_res;

        $order_id = $order_res['order']['id'];

        // 2. Create Invoice
        $invoice_body = [
            'idempotency_key' => uniqid('inv_'),
            'invoice' => [
                'location_id' => $this->location_id,
                'order_id' => $order_id,
                'payment_requests' => [[
                    'request_type' => 'BALANCE',
                    'due_date' => $data['due_date'],
                    'tipping_enabled' => false
                ]],
                'title' => $data['title'],
            ]
        ];

        $inv_res = $this->request('/invoices', 'POST', $invoice_body);
        if (is_wp_error($inv_res)) return $inv_res;

        $invoice = $inv_res['invoice'];

        // 3. Publish
        $pub_res = $this->request("/invoices/{$invoice['id']}/publish", 'POST', [
            'idempotency_key' => uniqid('pub_'),
            'version' => $invoice['version']
        ]);

        if (!is_wp_error($pub_res)) $invoice = $pub_res['invoice'];

        return [
            'id' => $invoice['id'],
            'order_id' => $order_id,
            'status' => $invoice['status'],
            'payment_link' => $invoice['public_url'] ?? ''
        ];
    }

    private function request(string $endpoint, string $method, array $body = []): array|WP_Error {
        $url = $this->base_url . $endpoint;
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'application/json',
                'Square-Version' => '2023-10-20'
            ],
            'timeout' => 15, // Fail fast
            'blocking' => true
        ];

        if (!empty($body)) $args['body'] = json_encode($body);

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) return $response;

        $body_json = json_decode(wp_remote_retrieve_body($response), true);
        if (wp_remote_retrieve_response_code($response) >= 400) {
            return new WP_Error('api_error', $body_json['errors'][0]['detail'] ?? 'Square API Error');
        }

        return $body_json;
    }
}
