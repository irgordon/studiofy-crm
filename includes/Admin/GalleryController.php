<?php
/**
 * Gallery Controller
 * @package Studiofy\Admin
 * @version 2.1.9
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
        
        register_rest_route('studiofy/v1', '/galleries/files/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_file'],
            'permission_callback' => fn() => current_user_can('upload_files')
        ]);
    }

    public function get_gallery_files(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;
        $id = $request->get_param('id');
        $files = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_gallery_files WHERE gallery_id = %d ORDER BY created_at DESC", $id));
        return new \WP_REST_Response($files, 200);
    }

    public function delete_file(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;
        $id = $request->get_param('id');
        $file = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_gallery_files WHERE id = %d", $id));
        
        if ($file) {
            // Delete actual file (if it exists inside studiofy_galleries)
            if (file_exists($file->file_path)) {
                unlink($file->file_path);
            }
            $wpdb->delete($wpdb->prefix . 'studiofy_gallery_files', ['id' => $id]);
            return new \WP_REST_Response(['success' => true], 200);
        }
        return new \WP_REST_Response(['success' => false], 404);
    }

    public function render_page(): void {
        global $wpdb;
        $galleries = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}studiofy_galleries ORDER BY created_at DESC");
        
        ?>
        <div class="wrap studiofy-explorer-wrap">
            <h1 class="wp-heading-inline">Image Galleries</h1>
            <button class="page-title-action" id="btn-create-gallery">Add New Gallery</button>
            <hr class="wp-header-end">

            <div class="studiofy-explorer-container">
                <div class="studiofy-explorer-sidebar" id="folder-list">
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
                        <button class="button button-primary" id="btn-upload-media" disabled>Upload Media</button>
                        <span id="current-folder-label" style="margin-left:15px; color:#666;">No Gallery Selected</span>
                    </div>
                    
                    <div class="studiofy-file-grid" id="file-grid">
                        <div class="studiofy-empty-state-small">
                            <span class="dashicons dashicons-format-image"></span>
                            <p>Select a gallery on the left to view images.</p>
                        </div>
                    </div>
                </div>

                <div class="studiofy-meta-sidebar" id="meta-sidebar">
                    <div class="meta-header">
                        <h3>Details</h3>
                        <button class="close-meta">&times;</button>
                    </div>
                    
                    <div id="meta-content" style="display:none;">
                        <div class="meta-preview" id="meta-preview"></div>
                        <div class="meta-info">
                            <p><strong>Name:</strong> <span id="meta-title"></span></p>
                            <p><strong>Size:</strong> <span id="meta-size"></span></p>
                            <p><strong>Type:</strong> <span id="meta-type"></span></p>
                            <p><strong>Dimensions:</strong> <span id="meta-dims"></span></p>
                        </div>
                        <div class="meta-actions">
                            <button class="button" id="btn-resize">Resize</button>
                            <button class="button" id="btn-move">Move</button>
                            <button class="button button-link-delete" id="btn-delete-file">Delete Permanently</button>
                        </div>
                    </div>
                    
                    <div id="meta-empty">
                        <p>No image selected.</p>
                    </div>
                </div>
            </div>
        </div>

        <form id="upload-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data" style="display:none;">
            <input type="hidden" name="action" value="studiofy_save_gallery">
            <input type="hidden" name="id" id="upload-gallery-id">
            <?php wp_nonce_field('save_gallery', 'studiofy_nonce'); ?>
            <input type="file" name="gallery_files[]" id="file-input" multiple accept=".jpg,.jpeg,.png,.gif,.cr2">
        </form>
        
        <?php
    }

    public function handle_save(): void {
        check_admin_referer('save_gallery', 'studiofy_nonce');
        global $wpdb;
        $id = (int)$_POST['id'];
        
        if (!empty($_FILES['gallery_files']['name'][0])) {
            $upload = wp_upload_dir();
            $base_dir = $upload['basedir'] . '/studiofy_galleries/' . $id;
            $base_url = $upload['baseurl'] . '/studiofy_galleries/' . $id;
            if (!file_exists($base_dir)) mkdir($base_dir, 0755, true);

            $files = $_FILES['gallery_files'];
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === 0) {
                    $name = sanitize_file_name($files['name'][$i]);
                    $ext = pathinfo($name, PATHINFO_EXTENSION);
                    $target = $base_dir . '/' . $name;
                    $size = size_format(filesize($files['tmp_name'][$i]));
                    $dims = '';
                    
                    if(in_array(strtolower($ext), ['jpg','png'])) {
                        $info = getimagesize($files['tmp_name'][$i]);
                        $dims = $info ? $info[0].'x'.$info[1] : '';
                    }

                    if (move_uploaded_file($files['tmp_name'][$i], $target)) {
                        $wpdb->insert($wpdb->prefix.'studiofy_gallery_files', [
                            'gallery_id' => $id,
                            'uploaded_by' => get_current_user_id(),
                            'file_name' => $name,
                            'file_path' => $target,
                            'file_url' => $base_url . '/' . $name,
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
