<?php
/**
 * Gallery API Routes
 * @package Studiofy\Api
 * @version 2.0.4
 */

declare(strict_types=1);

namespace Studiofy\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class GalleryRoutes {
    public function init(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    public function register_routes(): void {
        register_rest_route('studiofy/v1', '/gallery/proof', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'submit_proofs'],
            'permission_callback' => '__return_true'
        ]);
    }
    public function submit_proofs(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $params = $request->get_json_params();
        $table = $wpdb->prefix . 'studiofy_gallery_selections';
        
        if(empty($params['photos']) || !is_array($params['photos'])) {
            return new WP_REST_Response(['success' => false], 400);
        }

        foreach ($params['photos'] as $pid) {
            $wpdb->insert($table, ['gallery_id' => $params['gallery_id'], 'attachment_id' => (int)$pid, 'status' => 'selected']);
        }
        return new WP_REST_Response(['success' => true], 200);
    }
}
