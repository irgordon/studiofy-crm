<?php
/**
 * Gallery Controller (Custom File Management)
 * @package Studiofy\Admin
 * @version 2.1.7
 */

declare(strict_types=1);

namespace Studiofy\Admin;

class GalleryController {

    public function init(): void {
        add_action('admin_post_studiofy_save_gallery', [$this, 'handle_save']);
    }

    public function render_page(): void {
        $action = $_GET['action'] ?? 'list';
        if ($action === 'create' || $action === 'view') {
            $this->render_form();
        } else {
            $this->render_list();
        }
    }

    private function render_list(): void {
        global $wpdb;
        $sql = "SELECT g.*, c.first_name, c.last_name, (SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_gallery_files WHERE gallery_id = g.id) as file_count 
                FROM {$wpdb->prefix}studiofy_galleries g
                LEFT JOIN {$wpdb->prefix}studiofy_customers c ON g.customer_id = c.id
                ORDER BY g.created_at DESC";
        $rows = $wpdb->get_results($sql);

        echo '<div class="wrap studiofy-dark-theme">';
        echo '<h1>Image Galleries <a href="?page=studiofy-galleries&action=create" class="page-title-action">New Gallery</a></h1>';
        echo '<hr class="wp-header-end">';

        // Empty State Check
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_galleries");
        if ($count == 0) {
            echo '<div class="studiofy-empty-state">';
            echo '<div class="empty-icon dashicons dashicons-format-gallery"></div>';
            echo '<h2>No galleries yet</h2>';
            echo '<p>Create your first image gallery to organize and showcase photos. Supports RAW, JPG, PNG.</p>';
            echo '<a href="?page=studiofy-galleries&action=create" class="button button-primary button-large">Create Gallery</a>';
            echo '</div>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Title</th><th>Customer</th><th>Files</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
            foreach ($rows as $r) {
                $customer = $r->first_name ? esc_html($r->first_name . ' ' . $r->last_name) : 'None';
                echo "<tr>
                    <td><strong>" . esc_html($r->title) . "</strong></td>
                    <td>$customer</td>
                    <td>" . esc_html($r->file_count) . "</td>
                    <td><span class='studiofy-badge active'>" . esc_html($r->status) . "</span></td>
                    <td><a href='?page=studiofy-galleries&action=view&id={$r->id}' class='button button-small'>Manage Photos</a></td>
                </tr>";
            }
            echo '</tbody></table>';
        }
        echo '</div>';
    }

    private function render_form(): void {
        global $wpdb;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $gallery = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_galleries WHERE id = %d", $id)) : null;
        $customers = $wpdb->get_results("SELECT id, first_name, last_name FROM {$wpdb->prefix}studiofy_customers");
        
        $files = [];
        if ($id) {
            $files = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_gallery_files WHERE gallery_id = %d", $id));
        }

        ?>
        <div class="wrap studiofy-dark-theme">
            <h1><?php echo $gallery ? 'Manage Gallery: ' . esc_html($gallery->title) : 'New Gallery'; ?></h1>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data" class="studiofy-card-form">
                <input type="hidden" name="action" value="studiofy_save_gallery">
                <?php wp_nonce_field('save_gallery', 'studiofy_nonce'); ?>
                <?php if($gallery) echo '<input type="hidden" name="id" value="'.$gallery->id.'">'; ?>

                <div class="studiofy-form-row">
                    <div class="studiofy-col"><label>Gallery Title *</label><input type="text" name="title" required class="widefat" value="<?php echo esc_attr($gallery->title??''); ?>"></div>
                    <div class="studiofy-col"><label>Customer</label>
                        <select name="customer_id" class="widefat">
                            <option value="">Select...</option>
                            <?php foreach($customers as $c) echo "<option value='{$c->id}' ".selected($gallery->customer_id??0, $c->id, false).">{$c->first_name} {$c->last_name}</option>"; ?>
                        </select>
                    </div>
                </div>

                <?php if ($gallery): ?>
                <hr>
                <h3>Upload Photos (RAW, JPG, PNG, GIF)</h3>
                <input type="file" name="gallery_files[]" multiple accept=".jpg,.jpeg,.png,.gif,.cr2,.nef,.arw" class="widefat">
                <p class="description">Files are stored securely in <code>/wp-content/uploads/studiofy_galleries/</code></p>
                <hr>
                <h3>Current Photos</h3>
                <div class="studiofy-grid">
                    <?php foreach($files as $f): ?>
                    <div class="studiofy-item">
                        <?php if (in_array(strtolower($f->file_type), ['jpg','jpeg','png','gif'])): ?>
                            <img src="<?php echo esc_url($f->file_url); ?>" style="width:100%; height:150px; object-fit:cover;">
                        <?php else: ?>
                            <div style="width:100%; height:150px; background:#333; color:#fff; display:flex; align-items:center; justify-content:center;">RAW FILE</div>
                        <?php endif; ?>
                        <div style="padding:5px; font-size:10px; color:#fff; background:#222; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;"><?php echo esc_html($f->file_name); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="studiofy-form-actions">
                    <button type="submit" class="button button-primary button-large"><?php echo $gallery ? 'Upload & Save' : 'Create Gallery'; ?></button>
                </div>
            </form>
        </div>
        <?php
    }

    public function handle_save(): void {
        check_admin_referer('save_gallery', 'studiofy_nonce');
        global $wpdb;
        
        $data = [
            'title' => sanitize_text_field($_POST['title']),
            'customer_id' => (int)$_POST['customer_id'],
            'status' => 'active'
        ];

        $gallery_id = 0;
        if (!empty($_POST['id'])) {
            $gallery_id = (int)$_POST['id'];
            $wpdb->update($wpdb->prefix.'studiofy_galleries', $data, ['id'=>$gallery_id]);
        } else {
            $wpdb->insert($wpdb->prefix.'studiofy_galleries', $data);
            $gallery_id = $wpdb->insert_id;
        }

        // Handle File Uploads
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
                    
                    if (move_uploaded_file($files['tmp_name'][$i], $target)) {
                        $wpdb->insert($wpdb->prefix.'studiofy_gallery_files', [
                            'gallery_id' => $gallery_id,
                            'file_name' => $name,
                            'file_path' => $target,
                            'file_url'  => $base_url . '/' . $name,
                            'file_type' => $ext
                        ]);
                        
                        // Apply Watermark if JPG/PNG
                        if(in_array(strtolower($ext), ['jpg','jpeg','png'])) {
                            $this->apply_watermark($target);
                        }
                    }
                }
            }
        }

        wp_redirect(admin_url('admin.php?page=studiofy-galleries&action=view&id='.$gallery_id));
        exit;
    }

    private function apply_watermark($file_path) {
        // Basic Text Watermark using GD
        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        if ($ext == 'jpg' || $ext == 'jpeg') $img = imagecreatefromjpeg($file_path);
        elseif ($ext == 'png') $img = imagecreatefrompng($file_path);
        else return;

        $color = imagecolorallocatealpha($img, 255, 255, 255, 60); // Semi-transparent white
        $text = "PROOF";
        imagestring($img, 5, 20, 20, $text, $color);
        
        if ($ext == 'png') imagepng($img, $file_path);
        else imagejpeg($img, $file_path, 80);
        imagedestroy($img);
    }
}
