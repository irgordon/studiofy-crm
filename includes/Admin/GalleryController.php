<?php
/**
 * Gallery Controller
 * @package Studiofy\Admin
 * @version 2.0.4
 */

declare(strict_types=1);

namespace Studiofy\Admin;

class GalleryController {

    public function init(): void {
        add_action('init', [$this, 'register_taxonomy']);
    }

    public function register_taxonomy(): void {
        register_taxonomy('studiofy_folder', 'attachment', [
            'labels' => ['name' => 'Gallery Folders'],
            'hierarchical' => true,
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true
        ]);
    }

    public function render_page(): void {
        $terms = get_terms(['taxonomy' => 'studiofy_folder', 'hide_empty' => false]);
        echo '<div class="wrap studiofy-dark-theme"><h1>Galleries</h1><p>Manage in Media Library.</p>';
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Folder</th><th>Count</th></tr></thead><tbody>';
        if(!is_wp_error($terms)) {
            foreach($terms as $t) echo "<tr><td>" . esc_html($t->name) . "</td><td>" . esc_html($t->count) . "</td></tr>";
        }
        echo '</tbody></table></div>';
    }
}
