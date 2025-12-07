<?php
/**
 * Gallery Shortcode
 * @package Studiofy\Frontend
 * @version 2.2.41
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
                    <?php foreach ($files as $file): ?>
                        <div class="studiofy-grid-item" data-file-id="<?php echo $file->id; ?>">
                            <img src="<?php echo esc_url($file->file_url); ?>" alt="<?php echo esc_attr($file->file_name); ?>" loading="lazy">
                            <div class="proof-overlay">
                                <button class="proof-btn approve" aria-label="Approve">✓</button>
                                <button class="proof-btn reject" aria-label="Reject">✗</button>
                            </div>
                            <div class="status-indicator"></div>
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

        // 1. Save Selections to Database
        $approved_count = 0;
        foreach($selections as $sel) {
            if($sel['status'] === 'approved') $approved_count++;
            
            // Upsert Logic (Delete old, insert new for simplicity in proofing rounds)
            $wpdb->delete($wpdb->prefix.'studiofy_gallery_selections', [
                'gallery_id' => $gallery_id, 
                'attachment_id' => (int)$sel['file_id']
            ]);
            
            $wpdb->insert($wpdb->prefix.'studiofy_gallery_selections', [
                'gallery_id' => $gallery_id,
                'attachment_id' => (int)$sel['file_id'],
                'status' => sanitize_text_field($sel['status']),
                'created_at' => current_time('mysql')
            ]);
        }

        // 2. Email Notification
        $admin_email = get_option('admin_email');
        $subject = "Proofing Completed: " . $gallery->title;
        $message = "Client has submitted selections for gallery: {$gallery->title}.\nTotal Approved: $approved_count\n\nLogin to Studiofy CRM to view details.";
        wp_mail($admin_email, $subject, $message);

        // 3. Update Project Kanban (The Fix)
        $project_title = 'Unknown Project';
        
        if ($gallery->customer_id) {
            // Priority: Find 'In Progress' Project first, then any project
            $project_row = $wpdb->get_row($wpdb->prepare(
                "SELECT id, title FROM {$wpdb->prefix}studiofy_projects 
                 WHERE customer_id = %d AND status = 'in_progress' 
                 ORDER BY created_at DESC LIMIT 1", 
                $gallery->customer_id
            ));

            // Fallback: Any project if no active one found
            if (!$project_row) {
                $project_row = $wpdb->get_row($wpdb->prepare(
                    "SELECT id, title FROM {$wpdb->prefix}studiofy_projects 
                     WHERE customer_id = %d ORDER BY created_at DESC LIMIT 1", 
                    $gallery->customer_id
                ));
            }

            if ($project_row) {
                $project_title = $project_row->title;
                
                // Ensure Milestone Exists for this project
                $m_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}studiofy_milestones WHERE project_id = %d AND name = 'General Tasks' LIMIT 1", $project_row->id));
                
                if (!$m_id) {
                    $wpdb->insert($wpdb->prefix.'studiofy_milestones', ['project_id' => $project_row->id, 'name' => 'General Tasks']);
                    $m_id = $wpdb->insert_id;
                }

                // Insert Task with 'todo' status to ensure visibility
                $wpdb->insert($wpdb->prefix.'studiofy_tasks', [
                    'milestone_id' => $m_id,
                    'title' => 'Proofs Approved: ' . $project_row->title,
                    'priority' => 'Urgent', 
                    'description' => "Client selected $approved_count images from gallery '{$gallery->title}'.",
                    'status' => 'todo', // Fixed: 'pending' might be hidden in some views, 'todo' is standard
                    'created_at' => current_time('mysql')
                ]);
            }
        }

        // 4. Clear Dashboard Cache (Critical Fix)
        // This forces the Dashboard & Project Cards to recalculate Task Counts immediately
        delete_transient('studiofy_dashboard_stats');

        $msg = "Selections submitted! Project '{$project_title}' has been updated with a new task.";
        wp_send_json_success(['message' => $msg]);
    }
}
