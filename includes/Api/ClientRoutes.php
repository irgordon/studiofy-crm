<?php
/**
 * Client API Routes
 * @package Studiofy\Api
 * @version 2.0.4
 */

declare(strict_types=1);

namespace Studiofy\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Studiofy\Security\Encryption;

class ClientRoutes {

    public function init(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        register_rest_route('studiofy/v1', '/clients', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'create_lead'],
            'permission_callback' => '__return_true',
            'args' => ['email' => ['required' => true, 'validate_callback' => function($p) { return is_email($p); }, 'sanitize_callback' => 'sanitize_email']]
        ]);
    }

    public function create_lead(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $params = $request->get_json_params();
        $email = sanitize_email($params['email']);
        $encryption = new Encryption();

        if ($wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}studiofy_clients WHERE email = %s", $email))) {
            return new WP_REST_Response(['success' => false, 'message' => 'Email exists'], 409);
        }

        $wpdb->insert($wpdb->prefix . 'studiofy_clients', [
            'status' => 'Lead',
            'first_name' => sanitize_text_field($params['first_name'] ?? ''),
            'last_name' => sanitize_text_field($params['last_name'] ?? ''),
            'email' => $email,
            'phone' => $encryption->encrypt(sanitize_text_field($params['phone'] ?? '')),
            'created_at' => current_time('mysql')
        ]);

        return new WP_REST_Response(['success' => true, 'id' => $wpdb->insert_id], 200);
    }
}
