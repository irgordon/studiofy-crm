<?php
/**
 * Project Controller
 * @package Studiofy\Admin
 * @version 2.0.5
 */

declare(strict_types=1);

namespace Studiofy\Admin;

use function Studiofy\studiofy_get_asset_version;

class ProjectController {

    public function init(): void {}

    public function render_page(): void {
        $this->render_kanban_board();
    }

    public function render_kanban_board(): void {
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('studiofy-kanban', STUDIOFY_URL . 'assets/js/kanban.js', ['jquery', 'jquery-ui-sortable'], studiofy_get_asset_version('assets/js/kanban.js'), true);
        wp_enqueue_script('studiofy-modal-js', STUDIOFY_URL . 'assets/js/project-modal.js', ['jquery'], studiofy_get_asset_version('assets/js/project-modal.js'), true);
        wp_enqueue_style('studiofy-modal-css', STUDIOFY_URL . 'assets/css/modal.css', [], studiofy_get_asset_version('assets/css/modal.css'));

        wp_localize_script('studiofy-kanban', 'studiofySettings', [
            'root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest')
        ]);
        
        global $wpdb;
        $table = $wpdb->prefix . 'studiofy_projects';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Projects</h1>
            <a href="#" class="page-title-action" id="btn-create-project">New Project</a>
            <hr class="wp-header-end">

            <div class="studiofy-toolbar">
                <input type="search" placeholder="Search projects by name, description, or client..." class="widefat" style="max-width:400px;">
                <div class="view-toggle">
                    <button class="button active">Kanban</button>
                    <button class="button">List</button>
                </div>
            </div>

            <?php if ($count == 0): ?>
                <div class="studiofy-empty-state">
                    <div class="empty-icon dashicons dashicons-grid-view"></div>
                    <h2>No projects yet</h2>
                    <p>Create your first project to start tracking work, deadlines, and budgets.</p>
                    <button class="button button-primary button-large" onclick="document.getElementById('btn-create-project').click()">Create Project</button>
                </div>
            <?php else: 
                $this->render_kanban_html();
            endif; ?>
        </div>
        <?php
        require_once STUDIOFY_PATH . 'templates/admin/modal-project.php';
    }

    private function render_kanban_html(): void {
        $projects = $this->get_projects_by_status();
        ?>
        <div class="studiofy-kanban-board">
            <?php foreach(['todo', 'in_progress', 'future'] as $status): ?>
            <div class="studiofy-column" data-status="<?php echo $status; ?>">
                <h2 class="studiofy-col-title"><?php echo ucfirst(str_replace('_',' ',$status)); ?></h2>
                <div class="studiofy-card-container">
                    <?php foreach ($projects[$status] as $project): ?>
                        <div class="studiofy-card" data-id="<?php echo esc_attr($project->id); ?>">
                            <div class="studiofy-card-header"><strong><?php echo esc_html($project->title); ?></strong></div>
                            <div class="studiofy-card-body"><p><?php echo esc_html($project->budget ? '$'.$project->budget : ''); ?></p></div>
                            <div class="studiofy-card-actions"><button class="button button-small" onclick="StudiofyKanban.editProject(<?php echo $project->id; ?>)">Manage</button></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function get_projects_by_status(): array {
        global $wpdb;
        $table = $wpdb->prefix . 'studiofy_projects';
        $results = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
        $sorted = ['todo' => [], 'in_progress' => [], 'future' => []];
        foreach ($results as $row) {
            if (isset($sorted[$row->status])) {
                $sorted[$row->status][] = $row;
            }
        }
        return $sorted;
    }
}
