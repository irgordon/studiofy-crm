<?php
declare(strict_types=1);
namespace Studiofy\Admin;
use Studiofy\Security\Encryption;

class ClientController {
    private Encryption $encryption;
    public function __construct() { $this->encryption = new Encryption(); }

    public function init(): void {
        add_action('admin_post_studiofy_save_client', [$this, 'handle_save']);
        add_action('admin_post_studiofy_delete_client', [$this, 'handle_delete']);
    }

    public function render_page(): void {
        global $wpdb;
        $items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}studiofy_clients ORDER BY created_at DESC");
        ?>
        <div class="wrap studiofy-dark-theme">
            <h1>Clients <button id="btn-new-client" class="page-title-action">New Client</button></h1>
            
            <?php if(empty($items)): ?>
                <div class="studiofy-empty-state"><p>No clients yet.</p></div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead><tr><th>Name</th><th>Contact</th><th>Company</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach($items as $i): 
                        $phone = $this->encryption->decrypt($i->phone);
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html($i->first_name.' '.$i->last_name); ?></strong></td>
                            <td><?php echo esc_html($i->email); ?><br><small><?php echo esc_html($phone); ?></small></td>
                            <td><?php echo esc_html($i->company); ?></td>
                            <td><span class="studiofy-badge"><?php echo esc_html($i->status); ?></span></td>
                            <td><a href="<?php echo wp_nonce_url(admin_url('admin_post.php?action=studiofy_delete_client&id='.$i->id), 'del_'.$i->id); ?>" onclick="return confirm('Delete?')" class="button button-small">Delete</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <div id="modal-new-client" class="studiofy-modal-overlay studiofy-hidden">
                <div class="studiofy-modal">
                    <div class="studiofy-modal-header"><h2>New Client</h2><span class="close-modal">&times;</span></div>
                    <form method="post" action="<?php echo admin_url('admin_post.php'); ?>" class="studiofy-modal-body">
                        <input type="hidden" name="action" value="studiofy_save_client">
                        <?php wp_nonce_field('save_client', 'studiofy_nonce'); ?>
                        <div class="studiofy-form-row"><input type="text" name="first_name" placeholder="First Name" required class="widefat"> <input type="text" name="last_name" placeholder="Last Name" class="widefat"></div>
                        <div class="studiofy-form-row"><input type="email" name="email" placeholder="Email" required class="widefat"> <input type="text" name="phone" placeholder="Phone" class="widefat"></div>
                        <input type="text" name="company" placeholder="Company" class="widefat" style="margin-bottom:10px;">
                        <input type="text" name="address" placeholder="Address" class="widefat" style="margin-bottom:10px;">
                        <select name="status" class="widefat" style="margin-bottom:10px;"><option>Lead</option><option>Active</option></select>
                        <textarea name="notes" placeholder="Notes" class="widefat"></textarea>
                        <button type="submit" class="button button-primary" style="margin-top:10px;">Create Client</button>
                    </form>
                </div>
            </div>
            <script>jQuery('#btn-new-client, .close-modal').click(function(){ jQuery('#modal-new-client').toggleClass('studiofy-hidden'); });</script>
        </div>
        <?php
    }

    public function handle_save(): void {
        check_admin_referer('save_client', 'studiofy_nonce');
        global $wpdb;
        $wpdb->insert($wpdb->prefix.'studiofy_clients', [
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => $this->encryption->encrypt(sanitize_text_field($_POST['phone'])),
            'address' => $this->encryption->encrypt(sanitize_text_field($_POST['address'])),
            'company' => sanitize_text_field($_POST['company']),
            'notes' => sanitize_textarea_field($_POST['notes']),
            'status' => sanitize_text_field($_POST['status']),
        ]);
        wp_redirect(admin_url('admin.php?page=studiofy-clients')); exit;
    }

    public function handle_delete(): void {
        check_admin_referer('del_'.$_GET['id']);
        global $wpdb;
        $wpdb->delete($wpdb->prefix.'studiofy_clients', ['id' => (int)$_GET['id']]);
        wp_redirect(admin_url('admin.php?page=studiofy-clients')); exit;
    }
}
