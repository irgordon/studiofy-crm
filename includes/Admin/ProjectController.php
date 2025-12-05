<?php
/**
 * Kanban Controller
 * @package Studiofy\Admin
 * @version 2.0.1
 */

declare(strict_types=1);

namespace Studiofy\Admin;

use function Studiofy\studiofy_get_asset_version;

class ProjectController {

    public function init(): void {
        // Init logic
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
        
        $this->render_html();
        require_once STUDIOFY_PATH . 'templates/admin/modal-project.php';
    }

    private function render_html(): void {
        $projects = $this->get_projects_by_status();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Project Management</h1>
            <hr class="wp-header-end">
            <div class="studiofy-kanban-board">
                <?php foreach(['todo', 'in_progress', 'future'] as $status): ?>
                <div class="studiofy-column" data-status="<?php echo $status; ?>">
                    <h2 class="studiofy-col-title"><?php echo ucfirst(str_replace('_',' ',$status)); ?></h2>
                    <div class="studiofy-card-container">
                        <?php foreach ($projects[$status] as $project): ?>
                            <div class="studiofy-card" data-id="<?php echo esc_attr($project->id); ?>">
                                <div class="studiofy-card-header"><strong><?php echo esc_html($project->title); ?></strong></div>
                                <div class="studiofy-card-actions"><button class="button button-small" onclick="StudiofyKanban.editProject(<?php echo $project->id; ?>)">Manage Tasks</button></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    private function get_projects_by_status(): array {
        global $wpdb;
        $table = $wpdb->prefix . 'studiofy_projects';
        $results = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
        $sorted = ['todo' => [], 'in_progress' => [], 'future' => []];
        foreach ($results as $row) if (isset($sorted[$row->status])) $sorted[$row->status][] = $row;
        return $sorted;
    }
}
