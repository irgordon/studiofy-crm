<?php
/**
 * Gallery Controller
 * @package Studiofy\Admin
 * @version 2.1.12
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

        register_rest_route('studiofy/v1', '/galleries/files/(?P<id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, 'update_file_meta'],
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
            if (file_exists($file->file_path)) unlink($file->file_path);
            $wpdb->delete($wpdb->prefix . 'studiofy_gallery_files', ['id' => $id]);
            return new \WP_REST_Response(['success' => true], 200);
        }
        return new \WP_REST_Response(['success' => false], 404);
    }

    public function update_file_meta(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;
        $id = $request->get_param('id');
        $params = $request->get_json_params();
        
        $updated = $wpdb->update($wpdb->prefix.'studiofy_gallery_files', [
            'meta_title' => sanitize_text_field($params['meta_title']),
            'meta_photographer' => sanitize_text_field($params['meta_photographer']),
            'meta_project' => sanitize_text_field($params['meta_project']),
        ], ['id' => $id]);

        return new \WP_REST_Response(['success' => (bool)$updated], 200);
    }

    public function render_page(): void {
        global $wpdb;
        // This query now works because Activator.php fixes the schema
        $galleries = $wpdb->get_results("SELECT g.*, p.post_title as wp_title FROM {$wpdb->prefix}studiofy_galleries g LEFT JOIN {$wpdb->posts} p ON g.wp_page_id = p.ID ORDER BY g.created_at DESC");
        
        ?>
        <div class="wrap studiofy-explorer-wrap">
            <h1 class="wp-heading-inline">Image Galleries</h1>
            <button class="page-title-action" id="btn-create-gallery">Add New Gallery</button>
            <hr class="wp-header-end">

            <div class="studiofy-explorer-container">
                <div class="studiofy-explorer-sidebar">
                    <h3>Galleries</h3>
                    <ul class="studiofy-folder-list">
                        <?php foreach($galleries as $g): 
                            $status_icon = $g->wp_page_id ? 'dashicons-lock' : 'dashicons-hidden';
                        ?>
                            <li class="folder-item" data-id="<?php echo $g->id; ?>">
                                <span class="dashicons <?php echo $status_icon; ?>"></span> 
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
                            <span class="dashicons dashicons-format-gallery"></span>
                            <p>Select a gallery folder to manage images.</p>
                        </div>
                    </div>
                </div>

                <div class="studiofy-meta-sidebar" id="meta-sidebar">
                    <div class="meta-header">
                        <h3>Image Details</h3>
                        <button class="close-meta">&times;</button>
                    </div>
                    
                    <div id="meta-content" style="display:none;">
                        <div class="meta-preview" id="meta-preview"></div>
                        <div class="meta-form">
                            <label>Title</label>
                            <input type="text" id="inp-meta-title" class="widefat">
                            
                            <label>Photographer</label>
                            <input type="text" id="inp-meta-author" class="widefat">
                            
                            <label>Project</label>
                            <input type="text" id="inp-meta-project" class="widefat">
                            
                            <div class="meta-stats">
                                <p><strong>Size:</strong> <span id="meta-size"></span></p>
                                <p><strong>Type:</strong> <span id="meta-type"></span></p>
                                <p><strong>Dims:</strong> <span id="meta-dims"></span></p>
                            </div>
                            
                            <button class="button button-primary" id="btn-save-meta" style="width:100%; margin-top:10px;">Save Metadata</button>
                        </div>
                        <div class="meta-actions">
                            <button class="button" id="btn-view-large">View Larger</button>
                            <button class="button button-link-delete" id="btn-delete-file">Delete</button>
                        </div>
                    </div>
                    
                    <div id="meta-empty">
                        <p>No image selected.</p>
                    </div>
                </div>
            </div>
        </div>

        <div id="studiofy-lightbox" class="studiofy-modal-overlay studiofy-hidden">
            <div class="studiofy-lightbox-content">
                <img id="lightbox-img" src="">
                <button class="close-modal" style="color:#fff; position:absolute; top:20px; right:20px; font-size:30px;">&times;</button>
            </div>
        </div>

        <form id="upload-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data" style="display:none;">
            <input type="hidden" name="action" value="studiofy_save_gallery">
            <input type="hidden" name="id" id="upload-gallery-id">
            <?php wp_nonce_field('save_gallery', 'studiofy_nonce'); ?>
            <input type="file" name="gallery_files[]" id="file-input" multiple accept=".jpg,.jpeg,.png,.gif,.cr2,.nef,.arw">
        </form>

        <div id="modal-new-gallery" class="studiofy-modal-overlay studiofy-hidden">
            <div class="studiofy-modal">
                <div class="studiofy-modal-header"><h2>Create Private Gallery</h2><button class="close-modal">&times;</button></div>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="studiofy-modal-body">
                    <input type="hidden" name="action" value="studiofy_save_gallery">
                    <?php wp_nonce_field('save_gallery', 'studiofy_nonce'); ?>
                    
                    <div class="studiofy-form-row"><label>Gallery Title *</label><input type="text" name="title" required class="widefat"></div>
                    <div class="studiofy-form-row"><label>Description</label><textarea name="description" class="widefat" rows="3"></textarea></div>
                    <div class="studiofy-form-row"><label>Password (Optional)</label><input type="text" name="password" class="widefat"></div>
                    
                    <div class="studiofy-form-actions">
                        <button type="button" class="button close-modal">Cancel</button>
                        <button type="submit" class="button button-primary">Create & Create Page</button>
                    </div>
                </form>
            </div>
        </div>
        
        <script>
            jQuery(document).ready(function($){
                $('#btn-create-gallery').click(function(){ $('#modal-new-gallery').removeClass('studiofy-hidden'); });
                $('.close-modal').click(function(){ $(this).closest('.studiofy-modal-overlay').addClass('studiofy-hidden'); });
            });
        </script>
        <?php
    }

    public function handle_save(): void {
        check_admin_referer('save_gallery', 'studiofy_nonce');
        global $wpdb;
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if ($id === 0) {
            $title = sanitize_text_field($_POST['title']);
            $pass  = sanitize_text_field($_POST['password']);
            $desc  = sanitize_textarea_field($_POST['description']);
            
            $page_id = wp_insert_post([
                'post_title'   => $title . ' - Proofing',
                'post_content' => '[studiofy_proof_gallery]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_password'=> $pass
            ]);

            $wpdb->insert($wpdb->prefix.'studiofy_galleries', [
                'title' => $title,
                'description' => $desc,
                'wp_page_id' => $page_id,
                'password' => $pass,
                'status' => 'active'
            ]);
            $id = $wpdb->insert_id;

            wp_update_post(['ID' => $page_id, 'post_content' => '[studiofy_proof_gallery id="' . $id . '"]']);
            
            wp_redirect(admin_url('admin.php?page=studiofy-galleries'));
            exit;
        }

        if (!empty($_FILES['gallery_files']['name'][0])) {
            $upload_dir = wp_upload_dir();
            $base_dir = $upload_dir['basedir'] . '/studiofy_galleries/' . $id;
            $base_url = $upload_dir['baseurl'] . '/studiofy_galleries/' . $id;
            
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

                    if(in_array(strtolower($ext), ['jpg','jpeg','png','gif'])) {
                        $info = getimagesize($files['tmp_name'][$i]);
                        if($info) $dims = $info[0] . ' x ' . $info[1];
                    }
                    
                    if (move_uploaded_file($files['tmp_name'][$i], $target)) {
                        $wpdb->insert($wpdb->prefix.'studiofy_gallery_files', [
                            'gallery_id' => $id,
                            'uploaded_by' => get_current_user_id(),
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
        wp_redirect(admin_url('admin.php?page=studiofy-galleries&action=view&id='.$id));
        exit;
    }
}
