<?php
/**
 * Gallery Shortcode
 * @package Studiofy\Frontend
 * @version 2.2.1
 */

declare(strict_types=1);

namespace Studiofy\Frontend;

use Studiofy\Media\Watermarker;

class GalleryShortcode {

    public function init(): void {
        add_shortcode('studiofy_proof_gallery', [$this, 'render']);
        add_action('wp_ajax_studiofy_submit_proof', [$this, 'handle_submit']);
        add_action('wp_ajax_nopriv_studiofy_submit_proof', [$this, 'handle_submit']);
    }

    public function render($atts): string {
        $atts = shortcode_atts(['id' => 0], $atts);
        if (!$atts['id']) return '<p>Gallery ID not found.</p>';

        $cache_key = 'studiofy_proof_html_' . $atts['id'];
        $cached = get_transient($cache_key);
        if ($cached && !is_user_logged_in()) {
            return $cached;
        }

        global $wpdb;
        $gallery = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_galleries WHERE id = %d", $atts['id']));
        $files = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_gallery_files WHERE gallery_id = %d", $atts['id']));
        $watermarker = new Watermarker();

        // Enqueue Assets
        wp_enqueue_style('studiofy-gallery-css', STUDIOFY_URL . 'assets/css/gallery.css', [], STUDIOFY_VERSION);
        wp_enqueue_script('studiofy-gallery-front', STUDIOFY_URL . 'assets/js/gallery-front.js', ['jquery'], STUDIOFY_VERSION, true);
        wp_localize_script('studiofy-gallery-front', 'studiofyProof', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'gallery_id' => $atts['id']
        ]);

        ob_start();
        ?>
        <div class="studiofy-proofing-container">
            <h2><?php echo esc_html($gallery->title); ?></h2>
            <p><?php echo esc_html($gallery->description); ?></p>
            
            <form id="proofing-form">
                <div class="studiofy-grid">
                    <?php foreach($files as $f): 
                        if(!in_array(strtolower($f->file_type), ['jpg','jpeg','png'])) continue;
                        $src = $watermarker->apply_watermark($f->id); 
                    ?>
                        <div class="studiofy-item">
                            <img src="<?php echo esc_url($src); ?>" loading="lazy" decoding="async" alt="<?php echo esc_attr($f->file_name); ?>">
                            <div class="proof-actions">
                                <label class="action-btn approve">
                                    <input type="radio" name="status[<?php echo $f->id; ?>]" value="approved">
                                    <span class="dashicons dashicons-yes"></span>
                                </label>
                                <label class="action-btn reject">
                                    <input type="radio" name="status[<?php echo $f->id; ?>]" value="rejected">
                                    <span class="dashicons dashicons-no"></span>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="submit-wrap">
                    <input type="text" name="client_name" placeholder="Your Name" required>
                    <button type="submit" class="button">Submit Selections</button>
                </div>
            </form>
        </div>
        <?php
        $output = ob_get_clean();
        set_transient($cache_key, $output, 3600); // Cache for 1 Hour
        return $output;
    }

    public function handle_submit(): void {
        $gallery_id = (int)$_POST['gallery_id'];
        $client = sanitize_text_field($_POST['client_name']);
        $selections = $_POST['status'] ?? [];

        $message = "Client: $client\n\nSelections:\n";
        foreach($selections as $id => $status) {
            $message .= "Image ID #$id: $status\n";
        }
        
        wp_mail(get_option('admin_email'), "Proofing Submission: Gallery #$gallery_id", $message);
        
        wp_send_json_success(['message' => 'Thank you! Your selections have been sent.']);
    }
}
