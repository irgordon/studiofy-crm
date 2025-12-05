<?php
/**
 * Gallery Controller
 * @package Studiofy\Admin
 * @version 2.0.5
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
        $count = count($terms);
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Image Galleries</h1>
            <a href="upload.php?mode=grid" class="page-title-action">New Gallery</a>
            <hr class="wp-header-end">

            <div class="studiofy-toolbar">
                <input type="search" placeholder="Search galleries..." class="widefat" style="max-width:400px;">
            </div>

            <?php if ($count == 0 || is_wp_error($terms)): ?>
                <div class="studiofy-empty-state">
                    <div class="empty-icon dashicons dashicons-format-gallery"></div>
                    <h2>No galleries yet</h2>
                    <p>Create your first image gallery to organize and showcase photos.</p>
                    <a href="upload.php?mode=grid" class="button button-primary button-large">Create Gallery</a>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead><tr><th>Gallery Name</th><th>Photos</th><th>Proofing Status</th></tr></thead>
                    <tbody>
                        <?php foreach($terms as $t): ?>
                        <tr>
                            <td><strong><?php echo esc_html($t->name); ?></strong></td>
                            <td><?php echo esc_html($t->count); ?></td>
                            <td><span class="studiofy-badge active">Active</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
}
