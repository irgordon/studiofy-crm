<?php
/**
 * Booking API
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
        $namespace = 'studiofy/v1';

        // GET: Check Availability for a specific month
        register_rest_route($namespace, '/bookings/availability', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_availability'],
            'permission_callback' => '__return_true',
            'args' => [
                'month' => ['required' => true], // Format: YYYY-MM
            ]
        ]);

        // POST: Create Booking
        register_rest_route($namespace, '/bookings', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'create_booking'],
            'permission_callback' => '__return_true',
            'args' => [
                'date' => ['required' => true],
                'time' => ['required' => true],
                'email' => ['required' => true, 'validate_callback' => function($p){ return is_email($p); }]
            ]
        ]);
    }

    public function get_availability(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $month = sanitize_text_field($request->get_param('month')); // 2023-10
        
        // Fetch existing bookings to block off slots
        $table = $wpdb->prefix . 'studiofy_bookings';
        $booked = $wpdb->get_results($wpdb->prepare(
            "SELECT booking_date, booking_time FROM $table WHERE booking_date LIKE %s AND status != 'cancelled'",
            $month . '%'
        ));

        // Transform to simple array of "YYYY-MM-DD HH:MM:SS"
        $blocked_slots = [];
        foreach ($booked as $b) {
            $blocked_slots[] = $b->booking_date . ' ' . $b->booking_time;
        }

        return new WP_REST_Response(['blocked' => $blocked_slots], 200);
    }

    public function create_booking(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $params = $request->get_json_params();
        $table = $wpdb->prefix . 'studiofy_bookings';

        // 1. Double Check Availability
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE booking_date = %s AND booking_time = %s AND status != 'cancelled'",
            $params['date'], $params['time']
        ));

        if ($exists) {
            return new WP_REST_Response(['success' => false, 'message' => 'Slot already taken.'], 409);
        }

        // 2. Insert
        $wpdb->insert($table, [
            'guest_name' => sanitize_text_field($params['name']),
            'guest_email' => sanitize_email($params['email']),
            'service_type' => sanitize_text_field($params['service']),
            'booking_date' => $params['date'],
            'booking_time' => $params['time'],
            'status' => 'pending'
        ]);

        return new WP_REST_Response(['success' => true, 'message' => 'Booking requested!'], 200);
    }
}
