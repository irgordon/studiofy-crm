<?php
/**
 * Kanban API Routes
 * @package Studiofy\Api
 * @version 2.0.4
 */

declare(strict_types=1);

namespace Studiofy\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class KanbanRoutes {

    public function init(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        register_rest_route('studiofy/v1', '/projects/update-status', [
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => [$this, 'update_project_status'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            }
        ]);
    }

    public function update_project_status(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $table = $wpdb->prefix . 'studiofy_projects';

        $project_id = (int) $request->get_param('id');
        $status = sanitize_text_field($request->get_param('status'));

        $wpdb->update($table, ['status' => $status], ['id' => $project_id]);

        return new WP_REST_Response(['success' => true], 200);
    }
}
