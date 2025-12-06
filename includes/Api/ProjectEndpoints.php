<?php
/**
 * Project Details API Routes
 * @package Studiofy\Api
 * @version 2.2.11
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
            foreach($tasks as $task) {
                $task->checklist = json_decode($task->checklist_json ?: '[]');
            }
            $milestone->tasks = $tasks;
            $data[] = $milestone;
        }

        return new WP_REST_Response($data, 200);
    }

    public function save_task(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $table = $wpdb->prefix . 'studiofy_tasks';
        $params = $request->get_json_params();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Studiofy Task Save Params: ' . print_r($params, true));
        }

        $data = [
            'milestone_id' => (int) ($params['milestone_id'] ?? 0),
            'title'        => sanitize_text_field($params['title'] ?? ''),
            'priority'     => sanitize_text_field($params['priority'] ?? 'Medium'),
            'description'  => sanitize_textarea_field($params['description'] ?? ''),
            'checklist_json' => json_encode($params['checklist'] ?? []),
            'status'       => sanitize_text_field($params['status'] ?? 'pending')
        ];

        // Ensure Milestone Exists logic
        if ($data['milestone_id'] === 0 && !empty($params['project_id'])) {
             $proj_id = (int)$params['project_id'];
             $m_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}studiofy_milestones WHERE project_id = %d LIMIT 1", $proj_id));
             
             if (!$m_id) {
                 $wpdb->insert($wpdb->prefix.'studiofy_milestones', ['project_id' => $proj_id, 'name' => 'General Tasks']);
                 $m_id = $wpdb->insert_id;
             }
             $data['milestone_id'] = $m_id;
        }

        if ($data['milestone_id'] === 0) {
            return new WP_REST_Response(['success' => false, 'message' => 'Invalid Milestone ID'], 400);
        }

        $result = false;
        if (!empty($params['id'])) {
            $result = $wpdb->update($table, $data, ['id' => (int) $params['id']]);
            $task_id = (int) $params['id'];
        } else {
            $result = $wpdb->insert($table, array_merge($data, ['created_at' => current_time('mysql')]));
            $task_id = $wpdb->insert_id;
        }

        if ($result === false) {
             error_log('Studiofy DB Error: ' . $wpdb->last_error);
             return new WP_REST_Response(['success' => false, 'message' => 'Database Error: ' . $wpdb->last_error], 500);
        }

        return new WP_REST_Response(['success' => true, 'id' => $task_id, 'data' => $data], 200);
    }
}
