<?php
/**
 * Gallery Controller
 * @package Studiofy\Admin
 * @version 2.2.23
 */

declare(strict_types=1);

namespace Studiofy\Admin;

class GalleryController {

    public function init(): void {
        add_action('admin_post_studiofy_save_gallery', [$this, 'handle_save']);
        add_action('admin_post_studiofy_delete_gallery', [$this, 'handle_delete_gallery']);
        add_action('wp_ajax_studiofy_create_gallery_page', [$this, 'ajax_create_page']);
        add_action('wp_ajax_studiofy_gallery_upload_chunk', [$this, 'handle_chunk_upload']); // New Chunk Handler
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        register_rest_route('studiofy/v1', '/galleries/(?P<id>\d+)/files', ['methods' => 'GET', 'callback' => [$this, 'get_gallery_files'], 'permission_callback' => fn() => current_user_can('upload_files')]);
        register_rest_route('studiofy/v1', '/galleries/files/(?P<id>\d+)', ['methods' => 'DELETE', 'callback' => [$this, 'delete_file'], 'permission_callback' => fn() => current_user_can('upload_files')]);
        register_rest_route('studiofy/v1', '/galleries/files/(?P<id>\d+)', ['methods' => 'POST', 'callback' => [$this, 'update_file_meta'], 'permission_callback' => fn() => current_user_can('upload_files')]);
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

    /**
     * Handle Chunked Uploads via Streaming
     * @see https://developer.yahoo.com/performance/rules.html (Flush buffer, memory mgmt)
     */
    public function handle_chunk_upload(): void {
        // 1. Security Check
        check_ajax_referer('save_gallery', 'nonce');
        if (!current_user_can('upload_files')) wp_send_json_error('Unauthorized');

        // 2. Parameters
        $gallery_id = (int)$_POST['gallery_id'];
        $file_name  = sanitize_file_name($_POST['file_name']);
        $chunk_idx  = (int)$_POST['chunk_index'];
        $total_chunks = (int)$_POST['total_chunks'];

        // 3. Validation (Extensions)
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'cr2', 'nef', 'arw', 'dng', 'orf', 'raf'];
        if (!in_array($ext, $allowed)) wp_send_json_error('Invalid file type.');

        // 4. Setup Directories
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/studiofy_galleries/' . $gallery_id;
        if (!file_exists($target_dir)) mkdir($target_dir, 0755, true);

        // Temp path for re-assembly
        $temp_file = $target_dir . '/' . $file_name . '.part';
        $final_file = $target_dir . '/' . $file_name;

        // 5. Append Chunk (Streaming Logic)
        if (!empty($_FILES['file_chunk']['tmp_name'])) {
            $in = fopen($_FILES['file_chunk']['tmp_name'], 'rb');
            
            // Open temp file in 'append' mode (binary)
            $out = fopen($temp_file, $chunk_idx === 0 ? 'wb' : 'ab'); 

            if ($in && $out) {
                while (!feof($in)) {
                    fwrite($out, fread($in, 8192)); // 8KB Buffer
                }
                fclose($in);
                fclose($out);
            } else {
                wp_send_json_error('Server Write Error');
            }
            
            unlink($_FILES['file_chunk']['tmp_name']); // Cleanup temp chunk
        }

        // 6. Finalize if Last Chunk
        if ($chunk_idx === ($total_chunks - 1)) {
            rename($temp_file, $final_file);
            
            // Generate Metadata
            $filesize = size_format(filesize($final_file));
            $dims = '';
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $info = getimagesize($final_file);
                $dims = $info ? $info[0] . 'x' . $info[1] : '';
            }

            // DB Insert
            global $wpdb;
            $wpdb->insert($wpdb->prefix . 'studiofy_gallery_files', [
                'gallery_id' => $gallery_id,
                'uploaded_by' => get_current_user_id(),
                'file_name' => $file_name,
                'file_path' => $final_file,
                'file_url'  => $upload_dir['baseurl'] . '/studiofy_galleries/' . $gallery_id . '/' . $file_name,
                'file_type' => $ext,
                'dimensions' => $dims,
                'file_size' => $filesize,
                'created_at' => current_time('mysql')
            ]);

            wp_send_json_success(['status' => 'complete']);
        } else {
            wp_send_json_success(['status' => 'chunk_uploaded']);
        }
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
                        <button class="button button-primary" id="btn-upload-media" disabled>Upload Media (Chunked)</button>
                        <span id="current-folder-label" style="margin-left:15px; color:#666;">No Gallery Selected</span>
                    </div>
                    <div class="studiofy-file-grid" id="file-grid" role="region" aria-label="File Grid">
                        <div class="studiofy-empty-state-small"><span class="dashicons dashicons-format-gallery"></span><p>Select a gallery folder.</p></div>
                    </div>
                </div>
                
                <div class="studiofy-meta-sidebar" id="meta-sidebar" role="complementary" aria-label="Image Details">
                    <div class="meta-header"><h3>Details</h3><button class="close-meta" aria-label="Close Sidebar">&times;</button></div>
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
            if(empty($published)): ?><p>No private gallery pages created yet.</p><?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead><tr><th>Gallery ID</th><th>Name</th><th>Customer</th><th>Code</th><th>Images</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach($published as $pg): $cust = $pg->first_name ? esc_html($pg->first_name.' '.$pg->last_name) : 'Unassigned'; ?>
                        <tr><td><?php echo $pg->id; ?></td><td><strong><?php echo esc_html($pg->title); ?></strong></td><td><?php echo $cust; ?></td><td><code><?php echo esc_html($pg->password); ?></code></td><td><?php echo $pg->img_count; ?></td>
                        <td><a href="<?php echo admin_url('post.php?post='.$pg->wp_page_id.'&action=edit'); ?>" target="_blank">Change URL</a></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <input type="file" id="file-input" multiple accept=".jpg,.jpeg,.png,.gif,.cr2,.nef,.arw,.dng,.orf,.raf" style="display:none;">

        <div id="modal-upload-progress" class="studiofy-modal-overlay studiofy-hidden" role="dialog" aria-modal="true">
            <div class="studiofy-modal" style="width: 400px; text-align: center;">
                <h3>Uploading Files...</h3>
                <div class="studiofy-progress-container">
                    <div id="studiofy-progress-bar" class="studiofy-progress-bar"></div>
                </div>
                <p id="upload-status-text">Starting...</p>
            </div>
        </div>

        <div id="modal-new-gallery" class="studiofy-modal-overlay studiofy-hidden" role="dialog" aria-modal="true" style="display:none;">
            <div class="studiofy-modal">
                <div class="studiofy-modal-header"><h2 id="modal-title">Create New Gallery Folder</h2><button class="close-modal">&times;</button></div>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="studiofy-modal-body">
                    <input type="hidden" name="action" value="studiofy_save_gallery">
                    <?php wp_nonce_field('save_gallery', 'studiofy_nonce_create'); ?>
                    <div class="studiofy-form-row"><div class="studiofy-col"><label for="gallery-title">Title *</label><input type="text" name="title" id="gallery-title" required class="widefat" title="Title"></div></div>
                    <div class="studiofy-form-row"><div class="studiofy-col"><label for="gallery-desc">Description</label><textarea name="description" id="gallery-desc" class="widefat" rows="3" title="Desc"></textarea></div></div>
                    <div class="studiofy-form-row"><div class="studiofy-col"><label for="gallery-pass">Password (Optional)</label><input type="text" name="password" id="gallery-pass" class="widefat" title="Password"></div></div>
                    <div class="studiofy-form-actions"><button type="button" class="button close-modal">Cancel</button><button type="submit" class="button button-primary">Create Folder</button></div>
                </form>
            </div>
        </div>
        
        <div id="studiofy-lightbox" class="studiofy-modal-overlay studiofy-hidden" role="dialog"><div class="studiofy-lightbox-content"><img id="lightbox-img" src=""><button class="close-modal" style="color:#fff; position:absolute; top:20px; right:20px; font-size:30px;">&times;</button></div></div>
        <script>jQuery(document).ready(function($){ $('#btn-create-gallery').click(function(){ $('#modal-new-gallery').show().removeClass('studiofy-hidden'); }); $('.close-modal').click(function(){ $(this).closest('.studiofy-modal-overlay').hide().addClass('studiofy-hidden'); }); });</script>
        <?php
    }

    public function handle_save(): void {
        check_admin_referer('save_gallery', 'studiofy_nonce_create');
        global $wpdb;
        $title = sanitize_text_field($_POST['title']);
        $desc  = sanitize_textarea_field($_POST['description']);
        $pass  = sanitize_text_field($_POST['password']);
        $wpdb->insert($wpdb->prefix.'studiofy_galleries', ['title' => $title, 'description' => $desc, 'password' => $pass, 'status' => 'active']);
        wp_redirect(admin_url('admin.php?page=studiofy-galleries'));
        exit;
    }

    public function ajax_create_page(): void {
        check_ajax_referer('wp_rest', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        global $wpdb;
        $id = (int)$_POST['id'];
        $gallery = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_galleries WHERE id = %d", $id));
        if ($gallery->wp_page_id) {
            $link = get_edit_post_link($gallery->wp_page_id, '');
            header('Content-Type: application/json'); wp_send_json_success(['message' => 'Exists', 'redirect_url' => html_entity_decode($link)]); return;
        }
        $pass = !empty($gallery->password) ? $gallery->password : wp_generate_password(8, false);
        $pid = wp_insert_post(['post_title'=>$gallery->title.' - Proofing','post_content'=>'[studiofy_proof_gallery id="'.$id.'"]','post_status'=>'publish','post_type'=>'page','post_password'=>$pass]);
        if ($pid) {
            $wpdb->update($wpdb->prefix.'studiofy_galleries', ['wp_page_id'=>$pid, 'password'=>$pass], ['id'=>$id]);
            header('Content-Type: application/json'); wp_send_json_success(['message'=>'Created','redirect_url'=>html_entity_decode(get_edit_post_link($pid, ''))]);
        } else { header('Content-Type: application/json'); wp_send_json_error('Failed'); }
    }
}
