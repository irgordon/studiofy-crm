<?php
/**
 * Booking REST API
 * @package Studiofy\Api
 * @version 2.0.1
 */

declare(strict_types=1);

namespace Studiofy\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class BookingRoutes {

    public function init(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        register_rest_route('studiofy/v1', '/bookings', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'create_booking'],
            'permission_callback' => '__return_true',
            'args' => ['date' => ['required' => true], 'time' => ['required' => true], 'email' => ['required' => true]]
        ]);
    }

    public function create_booking(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $params = $request->get_json_params();
        $table = $wpdb->prefix . 'studiofy_bookings';

        // Check availability
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE booking_date = %s AND booking_time = %s AND status != 'cancelled'", $params['date'], $params['time']));
        if ($exists) return new WP_REST_Response(['success' => false, 'message' => 'Slot taken'], 409);

        $wpdb->insert($table, [
            'guest_name' => sanitize_text_field($params['name']),
            'guest_email' => sanitize_email($params['email']),
            'service_type' => sanitize_text_field($params['service']),
            'booking_date' => $params['date'],
            'booking_time' => $params['time'],
            'status' => 'pending'
        ]);

        return new WP_REST_Response(['success' => true], 200);
    }
}
