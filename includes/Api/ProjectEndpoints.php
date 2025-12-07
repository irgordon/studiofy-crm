<?php
/**
 * Project API Endpoints
 * @package Studiofy\Api
 * @version 2.2.55
 */

declare(strict_types=1);

namespace Studiofy\Api;

class ProjectEndpoints {

    public function init(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        register_rest_route('studiofy/v1', '/projects/(?P<id>\d+)', [
            'methods' => 'GET', 'callback' => [$this, 'get_project'], 'permission_callback' => fn() => current_user_can('manage_options')
        ]);
        register_rest_route('studiofy/v1', '/projects/(?P<id>\d+)/tasks', [
            'methods' => 'POST', 'callback' => [$this, 'add_task'], 'permission_callback' => fn() => current_user_can('manage_options')
        ]);
        register_rest_route('studiofy/v1', '/tasks/(?P<id>\d+)', [
            'methods' => 'POST', 'callback' => [$this, 'update_task'], 'permission_callback' => fn() => current_user_can('manage_options')
        ]);
    }

    public function get_project(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;
        $id = $request->get_param('id');
        $project = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_projects WHERE id = %d", $id));
        
        if (!$project) return new \WP_REST_Response(['message' => 'Project not found'], 404);

        // Fetch Tasks with new fields
        $tasks = $wpdb->get_results($wpdb->prepare(
            "SELECT t.* FROM {$wpdb->prefix}studiofy_tasks t 
             JOIN {$wpdb->prefix}studiofy_milestones m ON t.milestone_id = m.id 
             WHERE m.project_id = %d ORDER BY t.created_at DESC", 
            $id
        ));

        $project->tasks = $tasks;
        return new \WP_REST_Response($project, 200);
    }

    public function add_task(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;
        $id = $request->get_param('id');
        $params = $request->get_json_params();
        
        $m_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}studiofy_milestones WHERE project_id = %d LIMIT 1", $id));
        if (!$m_id) {
            $wpdb->insert($wpdb->prefix.'studiofy_milestones', ['project_id' => $id, 'name' => 'General Tasks']);
            $m_id = $wpdb->insert_id;
        }

        $data = [
            'milestone_id' => $m_id,
            'title' => sanitize_text_field($params['title']),
            'group_name' => sanitize_text_field($params['group'] ?? 'General'),
            'priority' => sanitize_text_field($params['priority'] ?? 'Medium'),
            'status' => sanitize_text_field($params['status'] ?? 'created'),
            'assignee_id' => (int)($params['assignee'] ?? 0),
            'start_date' => !empty($params['start_date']) ? sanitize_text_field($params['start_date']) : null,
            'due_date' => !empty($params['due_date']) ? sanitize_text_field($params['due_date']) : null,
            'created_at' => current_time('mysql')
        ];

        $wpdb->insert($wpdb->prefix.'studiofy_tasks', $data);
        $data['id'] = $wpdb->insert_id;

        return new \WP_REST_Response($data, 200);
    }

    public function update_task(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;
        $id = $request->get_param('id');
        $params = $request->get_json_params();
        $data = [];

        if (isset($params['status'])) $data['status'] = sanitize_text_field($params['status']);
        // Add other update fields if needed for direct edits, currently mostly status toggling via checkbox
        // but can be expanded for full edit
        
        if (!empty($data)) {
            $wpdb->update($wpdb->prefix.'studiofy_tasks', $data, ['id' => $id]);
        }
        
        return new \WP_REST_Response(['success' => true], 200);
    }
}
