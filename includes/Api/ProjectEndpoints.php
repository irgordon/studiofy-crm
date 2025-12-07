<?php
/**
 * Project API Endpoints
 * @package Studiofy\Api
 * @version 2.2.53
 */

declare(strict_types=1);

namespace Studiofy\Api;

class ProjectEndpoints {

    public function init(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        register_rest_route('studiofy/v1', '/projects/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_project'],
            'permission_callback' => fn() => current_user_can('manage_options')
        ]);

        register_rest_route('studiofy/v1', '/projects/(?P<id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'update_project'],
            'permission_callback' => fn() => current_user_can('manage_options')
        ]);

        register_rest_route('studiofy/v1', '/projects/(?P<id>\d+)/tasks', [
            'methods' => 'POST',
            'callback' => [$this, 'add_task'],
            'permission_callback' => fn() => current_user_can('manage_options')
        ]);

        register_rest_route('studiofy/v1', '/tasks/(?P<id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'update_task'],
            'permission_callback' => fn() => current_user_can('manage_options')
        ]);
    }

    public function get_project(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;
        $id = $request->get_param('id');
        $project = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_projects WHERE id = %d", $id));
        
        if (!$project) return new \WP_REST_Response(['message' => 'Project not found'], 404);

        // Fetch Tasks
        $tasks = $wpdb->get_results($wpdb->prepare(
            "SELECT t.* FROM {$wpdb->prefix}studiofy_tasks t 
             JOIN {$wpdb->prefix}studiofy_milestones m ON t.milestone_id = m.id 
             WHERE m.project_id = %d ORDER BY t.created_at DESC", 
            $id
        ));

        $project->tasks = $tasks;
        return new \WP_REST_Response($project, 200);
    }

    public function update_project(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;
        $id = $request->get_param('id');
        $params = $request->get_json_params();
        
        if (isset($params['status'])) {
            $wpdb->update($wpdb->prefix.'studiofy_projects', ['status' => sanitize_text_field($params['status'])], ['id' => $id]);
        }
        
        return new \WP_REST_Response(['success' => true], 200);
    }

    public function add_task(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;
        $id = $request->get_param('id');
        $params = $request->get_json_params();
        $title = sanitize_text_field($params['title']);

        // Find or create default milestone
        $m_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}studiofy_milestones WHERE project_id = %d LIMIT 1", $id));
        if (!$m_id) {
            $wpdb->insert($wpdb->prefix.'studiofy_milestones', ['project_id' => $id, 'name' => 'General Tasks']);
            $m_id = $wpdb->insert_id;
        }

        $wpdb->insert($wpdb->prefix.'studiofy_tasks', [
            'milestone_id' => $m_id,
            'title' => $title,
            'priority' => 'Medium',
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ]);

        return new \WP_REST_Response(['success' => true, 'id' => $wpdb->insert_id, 'title' => $title], 200);
    }

    public function update_task(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;
        $id = $request->get_param('id');
        $params = $request->get_json_params();
        
        if (isset($params['status'])) {
            $wpdb->update($wpdb->prefix.'studiofy_tasks', ['status' => sanitize_text_field($params['status'])], ['id' => $id]);
        }
        
        return new \WP_REST_Response(['success' => true], 200);
    }
}
