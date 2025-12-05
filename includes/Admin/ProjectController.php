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
    public function init(): void {} 
    public function render_kanban_board(): void {
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('studiofy-kanban', STUDIOFY_URL . 'assets/js/kanban.js', ['jquery', 'jquery-ui-sortable'], studiofy_get_asset_version('assets/js/kanban.js'), true);
        wp_enqueue_script('studiofy-modal-js', STUDIOFY_URL . 'assets/js/project-modal.js', ['jquery'], studiofy_get_asset_version('assets/js/project-modal.js'), true);
        wp_enqueue_style('studiofy-modal-css', STUDIOFY_URL . 'assets/css/modal.css', [], studiofy_get_asset_version('assets/css/modal.css'));
        // ...
    }
}
