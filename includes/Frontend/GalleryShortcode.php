<?php
/**
 * Gallery Shortcode
 * @package Studiofy\Frontend
 * @version 2.2.24
 */

declare(strict_types=1);

namespace Studiofy\Frontend;

class GalleryShortcode {

    public function init(): void {
        add_shortcode('studiofy_proof_gallery', [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets(): void {
        wp_register_style('studiofy-gallery-front', STUDIOFY_URL . 'assets/css/gallery.css', [], STUDIOFY_VERSION);
    }

    public function render($atts): string {
        $atts = shortcode_atts(['id' => 0], $atts);
        $gallery_id = (int) $atts['id'];

        if (!$gallery_id) {
            return '<p>Gallery ID not provided.</p>';
        }

        global $wpdb;
        $gallery = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_galleries WHERE id = %d", $gallery_id));
        
        if (!$gallery) {
            return '<p>Gallery not found.</p>';
        }

        $files = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_gallery_files WHERE gallery_id = %d ORDER BY created_at DESC", $gallery_id));

        wp_enqueue_style('studiofy-gallery-front');

        ob_start();
        ?>
        <div class="studiofy-frontend-gallery">
            <div class="gallery-header">
                <h2><?php echo esc_html($gallery->title); ?></h2>
                <p><?php echo esc_html($gallery->description); ?></p>
            </div>
            
            <?php if (empty($files)): ?>
                <p>No images found in this gallery.</p>
            <?php else: ?>
                <div class="studiofy-grid">
                    <?php foreach ($files as $file): ?>
                        <div class="studiofy-grid-item">
                            <img src="<?php echo esc_url($file->file_url); ?>" alt="<?php echo esc_attr($file->file_name); ?>" loading="lazy">
                            <div class="item-overlay">
                                <span><?php echo esc_html($file->file_name); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
