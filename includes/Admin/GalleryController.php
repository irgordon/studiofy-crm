<?php
/**
 * Gallery Controller
 * @package Studiofy\Admin
 * @version 2.1.8
 */

declare(strict_types=1);

namespace Studiofy\Admin;

class GalleryController {

    public function init(): void {
        add_action('admin_post_studiofy_save_gallery', [$this, 'handle_save']);
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        register_rest_route('studiofy/v1', '/galleries/(?P<id>\d+)/files', [
            'methods' => 'GET',
            'callback' => [$this, 'get_gallery_files'],
            'permission_callback' => fn() => current_user_can('upload_files')
        ]);
    }

    public function get_gallery_files(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;
        $id = $request->get_param('id');
        
        $sql = "SELECT f.*, u.display_name as photographer, p.title as project_name 
                FROM {$wpdb->prefix}studiofy_gallery_files f
                LEFT JOIN {$wpdb->users} u ON f.uploaded_by = u.ID
                LEFT JOIN {$wpdb->prefix}studiofy_galleries g ON f.gallery_id = g.id
                LEFT JOIN {$wpdb->prefix}studiofy_projects p ON g.customer_id = p.customer_id
                WHERE f.gallery_id = %d ORDER BY f.created_at DESC";
        
        $files = $wpdb->get_results($wpdb->prepare($sql, $id));
        return new \WP_REST_Response($files, 200);
    }

    public function render_page(): void {
        global $wpdb;
        $galleries = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}studiofy_galleries ORDER BY created_at DESC");
        
        ?>
        <div class="wrap studiofy-explorer-wrap">
            <h1 class="wp-heading-inline">Media & Galleries</h1>
            <button class="page-title-action" id="btn-create-gallery">Add New Gallery</button>
            <hr class="wp-header-end">

            <h2 class="nav-tab-wrapper">
                <a href="#" class="nav-tab nav-tab-active" data-tab="images">All Images</a>
                <a href="#" class="nav-tab" data-tab="galleries">Galleries</a>
                <a href="#" class="nav-tab" data-tab="trash">Trash</a>
            </h2>

            <div class="studiofy-explorer-container">
                <div class="studiofy-explorer-sidebar">
                    <h3>Folders</h3>
                    <ul class="studiofy-folder-list">
                        <?php foreach($galleries as $g): ?>
                            <li class="folder-item" data-id="<?php echo $g->id; ?>">
                                <span class="dashicons dashicons-portfolio"></span> 
                                <?php echo esc_html($g->title); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="studiofy-explorer-content">
                    <div class="studiofy-toolbar">
                        <button class="button button-primary" id="btn-upload-media">Upload Media</button>
                        <span id="current-folder-label" style="margin-left:15px; color:#666;">Select a gallery folder...</span>
                    </div>
                    
                    <div class="studiofy-file-grid" id="file-grid">
                        <p style="padding:20px; color:#888;">Select a gallery on the left to view images.</p>
                    </div>
                </div>

                <div class="studiofy-meta-sidebar" id="meta-sidebar">
                    <button class="close-meta">&times;</button>
                    <h3>Attachment Details</h3>
                    <div class="meta-preview" id="meta-preview"></div>
                    <div class="meta-info">
                        <p><strong>Title:</strong> <span id="meta-title"></span></p>
                        <p><strong>Date:</strong> <span id="meta-date"></span></p>
                        <p><strong>Type:</strong> <span id="meta-type"></span></p>
                        <p><strong>Dimensions:</strong> <span id="meta-dims"></span></p>
                        <p><strong>Size:</strong> <span id="meta-size"></span></p>
                        <hr>
                        <p><strong>Photographer:</strong> <span id="meta-author"></span></p>
                        <p><strong>Project:</strong> <span id="meta-project"></span></p>
                    </div>
                </div>
            </div>
        </div>

        <form id="upload-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data" style="display:none;">
            <input type="hidden" name="action" value="studiofy_save_gallery">
            <input type="hidden" name="id" id="upload-gallery-id">
            <?php wp_nonce_field('save_gallery', 'studiofy_nonce'); ?>
            <input type="file" name="gallery_files[]" id="file-input" multiple accept=".jpg,.jpeg,.png,.gif,.cr2,.nef,.arw">
        </form>

        <?php
    }

    public function handle_save(): void {
        check_admin_referer('save_gallery', 'studiofy_nonce');
        global $wpdb;
        $user_id = get_current_user_id();
        $gallery_id = (int)$_POST['id'];

        if (!empty($_FILES['gallery_files']['name'][0])) {
            $upload_dir = wp_upload_dir();
            $base_dir = $upload_dir['basedir'] . '/studiofy_galleries/' . $gallery_id;
            $base_url = $upload_dir['baseurl'] . '/studiofy_galleries/' . $gallery_id;
            
            if (!file_exists($base_dir)) mkdir($base_dir, 0755, true);

            $files = $_FILES['gallery_files'];
            $count = count($files['name']);

            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] === 0) {
                    $name = sanitize_file_name($files['name'][$i]);
                    $ext = pathinfo($name, PATHINFO_EXTENSION);
                    $target = $base_dir . '/' . $name;
                    $size = size_format(filesize($files['tmp_name'][$i]));
                    $dims = '';

                    // Get Dimensions for Images
                    if(in_array(strtolower($ext), ['jpg','jpeg','png','gif'])) {
                        $info = getimagesize($files['tmp_name'][$i]);
                        if($info) $dims = $info[0] . ' x ' . $info[1];
                    }
                    
                    if (move_uploaded_file($files['tmp_name'][$i], $target)) {
                        $wpdb->insert($wpdb->prefix.'studiofy_gallery_files', [
                            'gallery_id' => $gallery_id,
                            'uploaded_by' => $user_id,
                            'file_name' => $name,
                            'file_path' => $target,
                            'file_url'  => $base_url . '/' . $name,
                            'file_type' => $ext,
                            'dimensions' => $dims,
                            'file_size' => $size,
                            'created_at' => current_time('mysql')
                        ]);
                    }
                }
            }
        }
        wp_redirect(admin_url('admin.php?page=studiofy-galleries')); exit;
    }
}
