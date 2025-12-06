<?php
/**
 * Gallery Controller
 * @package Studiofy\Admin
 * @version 2.2.16
 */

declare(strict_types=1);

namespace Studiofy\Admin;

class GalleryController {

    public function init(): void {
        add_action('admin_post_studiofy_save_gallery', [$this, 'handle_save']);
        add_action('admin_post_studiofy_delete_gallery', [$this, 'handle_delete_gallery']);
        add_action('wp_ajax_studiofy_create_gallery_page', [$this, 'ajax_create_page']);
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
        $galleries = $wpdb->get_results("SELECT g.*, p.post_title as wp_title, p.ID as page_id FROM {$wpdb->prefix}studiofy_galleries g LEFT JOIN {$wpdb->posts} p ON g.wp_page_id = p.ID ORDER BY g.created_at DESC");
        
        ?>
        <div class="wrap studiofy-explorer-wrap">
            <h1 class="wp-heading-inline">Image Galleries</h1>
            <div style="display:inline-block;">
                <button class="page-title-action" id="btn-create-gallery">Add New Gallery Folder</button>
                <button class="page-title-action" id="btn-create-page" style="margin-left:10px;" disabled>Create Private Gallery Page</button>
            </div>
            <hr class="wp-header-end">

            <div class="studiofy-explorer-container">
                <div class="studiofy-explorer-sidebar">
                    <h3>Folders</h3>
                    <ul class="studiofy-folder-list">
                        <?php foreach($galleries as $g): 
                            $status_icon = $g->page_id ? 'dashicons-admin-page' : 'dashicons-hidden';
                        ?>
                            <li class="folder-item" data-id="<?php echo $g->id; ?>" data-has-page="<?php echo $g->page_id ? 'true' : 'false'; ?>" tabindex="0" role="button">
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
                    <div class="studiofy-file-grid" id="file-grid" role="region" aria-label="File Grid">
                        <div class="studiofy-empty-state-small"><span class="dashicons dashicons-format-gallery"></span><p>Select a gallery folder.</p></div>
                    </div>
                </div>
                
                <div class="studiofy-meta-sidebar" id="meta-sidebar" role="complementary" aria-label="Image Details">
                    <div class="meta-header">
                        <h3>Details</h3>
                        <button class="close-meta" aria-label="Close Sidebar">&times;</button>
                    </div>
                    <div id="meta-content" style="display:none;">
                        <div class="meta-preview" id="meta-preview"></div>
                        <div class="meta-form">
                           <label for="inp-meta-title">Title</label><input type="text" id="inp-meta-title" class="widefat" placeholder="Title" title="Title">
                           <label for="inp-meta-author">Photographer</label><input type="text" id="inp-meta-author" class="widefat" placeholder="Photographer" title="Photographer">
                           <label for="inp-meta-project">Project</label><input type="text" id="inp-meta-project" class="widefat" placeholder="Project" title="Project">
                           <div class="meta-stats"><p><strong>Size:</strong> <span id="meta-size"></span></p><p><strong>Type:</strong> <span id="meta-type"></span></p><p><strong>Dims:</strong> <span id="meta-dims"></span></p></div>
                           <button class="button button-primary" id="btn-save-meta" style="width:100%; margin-top:10px;">Save Metadata</button>
                        </div>
                        <div class="meta-actions">
                             <button class="button" id="btn-view-large">View Larger</button>
                             <button class="button button-link-delete" id="btn-delete-file">Delete File</button>
                        </div>
                    </div>
                    <div id="meta-empty"><p>No image selected.</p></div>
                </div>
            </div>

            <h2>Published Private Galleries</h2>
            <?php 
            $sql = "SELECT g.*, c.first_name, c.last_name, (SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_gallery_files WHERE gallery_id = g.id) as img_count FROM {$wpdb->prefix}studiofy_galleries g LEFT JOIN {$wpdb->prefix}studiofy_customers c ON g.customer_id = c.id WHERE g.wp_page_id IS NOT NULL ORDER BY g.created_at DESC";
            $published = $wpdb->get_results($sql);
            
            if(empty($published)): ?>
                <p>No private gallery pages created yet.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead><tr><th>Gallery ID</th><th>Private Gallery Name</th><th>Customer</th><th>Access Code</th><th>Images</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach($published as $pg): 
                            $cust = $pg->first_name ? esc_html($pg->first_name.' '.$pg->last_name) : 'Unassigned';
                            $edit_link = admin_url('post.php?post='.$pg->wp_page_id.'&action=edit');
                            $del_link = wp_nonce_url(admin_url('admin-post.php?action=studiofy_delete_gallery&id='.$pg->id), 'del_gal_'.$pg->id);
                        ?>
                        <tr>
                            <td><?php echo $pg->id; ?></td>
                            <td><strong><a href="?page=studiofy-galleries&action=view&id=<?php echo $pg->id; ?>"><?php echo esc_html($pg->title); ?></a></strong></td>
                            <td><?php echo $cust; ?></td>
                            <td><code><?php echo esc_html($pg->password); ?></code></td>
                            <td><?php echo $pg->img_count; ?></td>
                            <td>
                                <a href="<?php echo $edit_link; ?>" target="_blank">Change URL</a> | 
                                <a href="<?php echo $del_link; ?>" onclick="return confirm('Delete gallery?');" style="color:#b32d2e;">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <form id="upload-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data" style="display:none;">
            <input type="hidden" name="action" value="studiofy_save_gallery">
            <input type="hidden" name="id" id="upload-gallery-id">
            <?php wp_nonce_field('save_gallery', 'studiofy_nonce_upload'); ?>
            <label for="file-input" class="screen-reader-text">Upload Files</label>
            <input type="file" name="gallery_files[]" id="file-input" multiple accept=".jpg,.jpeg,.png,.gif,.cr2,.nef,.arw">
        </form>

        <div id="modal-new-gallery" class="studiofy-modal-overlay studiofy-hidden" role="dialog" aria-modal="true" aria-labelledby="modal-title" style="display:none;">
            <div class="studiofy-modal">
                <div class="studiofy-modal-header">
                    <h2 id="modal-title">Create New Gallery Folder</h2>
                    <button class="close-modal" aria-label="Close">&times;</button>
                </div>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="studiofy-modal-body">
                    <input type="hidden" name="action" value="studiofy_save_gallery">
                    <?php wp_nonce_field('save_gallery', 'studiofy_nonce_create'); ?>
                    <div class="studiofy-form-row">
                        <div class="studiofy-col">
                            <label for="gallery-title">Gallery Title *</label>
                            <input type="text" name="title" id="gallery-title" required class="widefat" title="Title">
                        </div>
                    </div>
                    <div class="studiofy-form-row">
                        <div class="studiofy-col">
                            <label for="gallery-desc">Description</label>
                            <textarea name="description" id="gallery-desc" class="widefat" rows="3" title="Description"></textarea>
                        </div>
                    </div>
                    <div class="studiofy-form-row">
                        <div class="studiofy-col">
                            <label for="gallery-pass">Access Password (Optional)</label>
                            <input type="text" name="password" id="gallery-pass" class="widefat" placeholder="Leave blank to auto-generate" title="Password">
                        </div>
                    </div>
                    <div class="studiofy-form-actions">
                        <button type="button" class="button close-modal">Cancel</button>
                        <button type="submit" class="button button-primary">Create Folder</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div id="studiofy-lightbox" class="studiofy-modal-overlay studiofy-hidden" role="dialog" aria-label="Image Preview">
            <div class="studiofy-lightbox-content">
                <img id="lightbox-img" src="" alt="Preview">
                <button class="close-modal" aria-label="Close Preview" style="color:#fff; position:absolute; top:20px; right:20px; font-size:30px;">&times;</button>
            </div>
        </div>

        <script>jQuery(document).ready(function($){ $('#btn-create-gallery').click(function(){ $('#modal-new-gallery').show().removeClass('studiofy-hidden'); }); $('.close-modal').click(function(){ $(this).closest('.studiofy-modal-overlay').hide().addClass('studiofy-hidden'); }); });</script>
        <?php
    }

    public function handle_save(): void {
        // Check either nonce depending on the form source
        if (isset($_POST['studiofy_nonce_create']) && wp_verify_nonce($_POST['studiofy_nonce_create'], 'save_gallery')) {
             $is_upload = false;
        } elseif (isset($_POST['studiofy_nonce_upload']) && wp_verify_nonce($_POST['studiofy_nonce_upload'], 'save_gallery')) {
             $is_upload = true;
        } else {
             wp_die('Security check failed (Duplicate ID Fix Applied)');
        }
        
        global $wpdb;
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        // New Gallery
        if ($id === 0 && !$is_upload) {
            $title = sanitize_text_field($_POST['title']);
            $desc  = sanitize_textarea_field($_POST['description']);
            $pass  = sanitize_text_field($_POST['password']);
            
            $wpdb->insert($wpdb->prefix.'studiofy_galleries', [
                'title' => $title,
                'description' => $desc,
                'password' => $pass,
                'status' => 'active'
            ]);
            wp_redirect(admin_url('admin.php?page=studiofy-galleries'));
            exit;
        }
        
        // Uploads
        if ($is_upload && !empty($_FILES['gallery_files']['name'][0])) {
             $upload_dir = wp_upload_dir();
             $base_dir = $upload_dir['basedir'] . '/studiofy_galleries/' . $id;
             $base_url = $upload_dir['baseurl'] . '/studiofy_galleries/' . $id;
             if (!file_exists($base_dir)) mkdir($base_dir, 0755, true);
             $files = $_FILES['gallery_files'];
             for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === 0) {
                    $name = sanitize_file_name($files['name'][$i]);
                    $target = $base_dir . '/' . $name;
                    if (move_uploaded_file($files['tmp_name'][$i], $target)) {
                         $ext = pathinfo($name, PATHINFO_EXTENSION);
                         $dims = '';
                         if(in_array(strtolower($ext), ['jpg','jpeg','png'])) {
                            $info = getimagesize($target);
                            $dims = $info ? $info[0].'x'.$info[1] : '';
                         }
                         $wpdb->insert($wpdb->prefix.'studiofy_gallery_files', [
                            'gallery_id' => $id,
                            'uploaded_by' => get_current_user_id(),
                            'file_name' => $name,
                            'file_path' => $target,
                            'file_url' => $base_url . '/' . $name,
                            'file_type' => $ext,
                            'dimensions' => $dims,
                            'file_size' => size_format(filesize($target)),
                            'created_at' => current_time('mysql')
                        ]);
                    }
                }
             }
        }
        wp_redirect(admin_url('admin.php?page=studiofy-galleries&action=view&id='.$id));
        exit;
    }

    public function handle_delete_gallery(): void {
        check_admin_referer('del_gal_' . $_GET['id']);
        global $wpdb;
        $id = (int)$_GET['id'];
        $page_id = $wpdb->get_var($wpdb->prepare("SELECT wp_page_id FROM {$wpdb->prefix}studiofy_galleries WHERE id = %d", $id));
        if ($page_id) wp_delete_post($page_id, true);
        $wpdb->delete($wpdb->prefix.'studiofy_galleries', ['id' => $id]);
        $wpdb->delete($wpdb->prefix.'studiofy_gallery_files', ['gallery_id' => $id]);
        $upload = wp_upload_dir();
        $dir = $upload['basedir'] . '/studiofy_galleries/' . $id;
        if(is_dir($dir)) { array_map('unlink', glob("$dir/*.*")); rmdir($dir); }
        wp_redirect(admin_url('admin.php?page=studiofy-galleries')); exit;
    }

    public function ajax_create_page(): void {
        check_ajax_referer('wp_rest', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        
        global $wpdb;
        $id = (int)$_POST['id'];
        $gallery = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_galleries WHERE id = %d", $id));
        
        if (!$gallery) wp_send_json_error('Gallery not found');
        
        // FIX: If page already exists, return success with existing URL instead of error
        if ($gallery->wp_page_id) {
            $link = get_edit_post_link($gallery->wp_page_id, '');
            wp_send_json_success(['message' => 'Page already exists.', 'redirect_url' => html_entity_decode($link)]);
            return;
        }
        
        $password = !empty($gallery->password) ? $gallery->password : wp_generate_password(8, false);

        $page_id = wp_insert_post([
            'post_title'   => $gallery->title . ' - Proofing',
            'post_content' => '[studiofy_proof_gallery id="' . $id . '"]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_password'=> $password
        ]);
        
        if ($page_id) {
            $wpdb->update($wpdb->prefix.'studiofy_galleries', ['wp_page_id' => $page_id, 'password' => $password], ['id' => $id]);
            $redirect = get_edit_post_link($page_id, ''); 
            header('Content-Type: application/json');
            wp_send_json_success(['message' => 'Page Created!', 'redirect_url' => html_entity_decode($redirect)]);
        } else {
            header('Content-Type: application/json');
            wp_send_json_error('Failed to create page');
        }
    }
}
