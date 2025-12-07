<?php
/**
 * Gallery Shortcode
 * @package Studiofy\Frontend
 * @version 2.2.48
 */

declare(strict_types=1);

namespace Studiofy\Frontend;

class GalleryShortcode {

    public function init(): void {
        add_shortcode('studiofy_proof_gallery', [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_studiofy_submit_proof', [$this, 'handle_submit']);
        add_action('wp_ajax_nopriv_studiofy_submit_proof', [$this, 'handle_submit']);
    }

    public function enqueue_assets(): void {
        wp_register_style('studiofy-gallery-front', STUDIOFY_URL . 'assets/css/gallery.css', [], STUDIOFY_VERSION);
        wp_register_script('studiofy-gallery-front-js', STUDIOFY_URL . 'assets/js/gallery-front.js', ['jquery'], STUDIOFY_VERSION, true);
        
        wp_localize_script('studiofy-gallery-front-js', 'studiofyProofSettings', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('studiofy_proof_submit')
        ]);
    }

    public function render($atts): string {
        $atts = shortcode_atts(['id' => 0], $atts);
        $gallery_id = (int) $atts['id'];

        if (!$gallery_id) return '<p>Gallery ID not provided.</p>';

        global $wpdb;
        $gallery = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_galleries WHERE id = %d", $gallery_id));
        if (!$gallery) return '<p>Gallery not found.</p>';

        $files = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_gallery_files WHERE gallery_id = %d ORDER BY created_at DESC", $gallery_id));

        wp_enqueue_style('studiofy-gallery-front');
        wp_enqueue_script('studiofy-gallery-front-js');

        ob_start();
        ?>
        <div class="studiofy-frontend-gallery" id="gallery-proof-<?php echo $gallery_id; ?>">
            <div class="gallery-header">
                <h2><?php echo esc_html($gallery->title); ?></h2>
                <p><?php echo esc_html($gallery->description); ?></p>
            </div>
            
            <?php if (empty($files)): ?>
                <p>No images found in this gallery.</p>
            <?php else: ?>
                <div class="studiofy-actions-top">
                    <button class="button studiofy-submit-proof" data-id="<?php echo $gallery_id; ?>">Submit Selections</button>
                </div>

                <div class="studiofy-grid">
                    <?php 
                    $counter = 100;
                    foreach ($files as $file): 
                        $img_id_display = '#' . sprintf('%04d', $counter++);
                    ?>
                        <div class="studiofy-grid-item" data-file-id="<?php echo $file->id; ?>">
                            <div class="img-wrapper">
                                <img src="<?php echo esc_url($file->file_url); ?>" alt="<?php echo esc_attr($file->file_name); ?>" loading="lazy">
                            </div>
                            
                            <div class="proof-toolbar">
                                <span class="img-id"><?php echo $img_id_display; ?></span>
                                <div class="proof-controls">
                                    <button class="proof-btn approve" aria-label="Approve">✓</button>
                                    <button class="proof-btn reject" aria-label="Reject">✗</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="studiofy-actions-bottom">
                    <button class="button studiofy-submit-proof" data-id="<?php echo $gallery_id; ?>">Submit Selections to Photographer</button>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_submit(): void {
        check_ajax_referer('studiofy_proof_submit', 'nonce');
        
        $gallery_id = (int)$_POST['gallery_id'];
        $selections = $_POST['selections'] ?? []; 

        global $wpdb;
        $gallery = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_galleries WHERE id = %d", $gallery_id));
        
        if (!$gallery) wp_send_json_error('Gallery not found');

        $approved_count = 0;
        foreach($selections as $sel) {
            if($sel['status'] === 'approved') $approved_count++;
            $wpdb->delete($wpdb->prefix.'studiofy_gallery_selections', ['gallery_id' => $gallery_id, 'attachment_id' => (int)$sel['file_id']]);
            $wpdb->insert($wpdb->prefix.'studiofy_gallery_selections', [
                'gallery_id' => $gallery_id,
                'attachment_id' => (int)$sel['file_id'],
                'status' => sanitize_text_field($sel['status']),
                'created_at' => current_time('mysql')
            ]);
        }

        $admin_email = get_option('admin_email');
        $subject = "Proofing Completed: " . $gallery->title;
        $message = "Client has submitted selections for gallery: {$gallery->title}.\nTotal Approved: $approved_count";
        wp_mail($admin_email, $subject, $message);

        if ($gallery->customer_id) {
            $project_row = $wpdb->get_row($wpdb->prepare(
                "SELECT id, title FROM {$wpdb->prefix}studiofy_projects 
                 WHERE customer_id = %d AND status = 'in_progress' 
                 ORDER BY created_at DESC LIMIT 1", 
                $gallery->customer_id
            ));

            if (!$project_row) {
                $project_row = $wpdb->get_row($wpdb->prepare(
                    "SELECT id, title FROM {$wpdb->prefix}studiofy_projects 
                     WHERE customer_id = %d ORDER BY created_at DESC LIMIT 1", 
                    $gallery->customer_id
                ));
            }

            if ($project_row) {
                $m_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}studiofy_milestones WHERE project_id = %d AND name = 'General Tasks' LIMIT 1", $project_row->id));
                if (!$m_id) {
                    $wpdb->insert($wpdb->prefix.'studiofy_milestones', ['project_id' => $project_row->id, 'name' => 'General Tasks']);
                    $m_id = $wpdb->insert_id;
                }

                $wpdb->insert($wpdb->prefix.'studiofy_tasks', [
                    'milestone_id' => $m_id,
                    'title' => 'Proofs Approved: ' . $project_row->title,
                    'priority' => 'Urgent', 
                    'description' => "Client selected $approved_count images from {$gallery->title}.",
                    'status' => 'todo', 
                    'created_at' => current_time('mysql')
                ]);
            }
        }
        
        delete_transient('studiofy_dashboard_stats');
        wp_send_json_success(['message' => 'Selections submitted successfully!']);
    }
}
