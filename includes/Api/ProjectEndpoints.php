<?php
/**
 * Project Details API Routes
 * @package Studiofy\Api
 * @version 2.0.5
 */

declare(strict_types=1);

namespace Studiofy\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class ProjectEndpoints {

    public function init(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        $namespace = 'studiofy/v1';
        register_rest_route($namespace, '/projects/(?P<id>\d+)/details', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_project_details'],
            'permission_callback' => fn() => current_user_can('manage_options')
        ]);
        register_rest_route($namespace, '/tasks', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'save_task'],
            'permission_callback' => fn() => current_user_can('manage_options')
        ]);
    }

    public function get_project_details(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $project_id = (int) $request->get_param('id');
        
        $milestones = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_milestones WHERE project_id = %d ORDER BY created_at ASC", $project_id));
        $data = [];
        foreach ($milestones as $milestone) {
            $tasks = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_tasks WHERE milestone_id = %d", $milestone->id));
            foreach($tasks as $task) $task->checklist = json_decode($task->checklist_json ?: '[]');
            $milestone->tasks = $tasks;
            $data[] = $milestone;
        }

        return new WP_REST_Response($data, 200);
    }

    public function save_task(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $table = $wpdb->prefix . 'studiofy_tasks';
        $params = $request->get_json_params();
        
        $data = [
            'milestone_id' => (int) $params['milestone_id'],
            'title'        => sanitize_text_field($params['title']),
            'priority'     => sanitize_text_field($params['priority']),
            'description'  => sanitize_textarea_field($params['description']),
            'checklist_json' => json_encode($params['checklist'] ?? []),
            'status'       => sanitize_text_field($params['status'] ?? 'pending')
        ];

        if (!empty($params['id'])) {
            $wpdb->update($table, $data, ['id' => (int) $params['id']]);
            $task_id = (int) $params['id'];
        } else {
            $wpdb->insert($table, $data);
            $task_id = $wpdb->insert_id;
        }

        return new WP_REST_Response(['success' => true, 'id' => $task_id], 200);
    }
}
