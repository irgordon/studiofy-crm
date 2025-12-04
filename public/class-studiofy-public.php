<?php
declare(strict_types=1);

class Studiofy_Public {
    private string $version;
    public function __construct( string $plugin_name, string $version ) { $this->version = $version; }
    public function enqueue_scripts(): void { /* Standard Enqueue */ }

    public function render_lead_shortcode(): string {
        ob_start();
        ?>
        <form action="<?php echo admin_url('admin-post.php'); ?>" method="post" class="studiofy-lead-form">
            <input type="hidden" name="action" value="studiofy_capture_lead">
            <input type="text" name="first_name" placeholder="First Name" required>
            <input type="text" name="last_name" placeholder="Last Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <textarea name="notes" placeholder="Tell us about your event..."></textarea>
            <button type="submit">Send Inquiry</button>
        </form>
        <?php
        return ob_get_clean();
    }

    public function process_lead_form(): void {
        global $wpdb;
        $wpdb->insert($wpdb->prefix.'studiofy_leads', [
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'notes' => sanitize_textarea_field($_POST['notes']),
            'status' => 'new'
        ]);
        wp_redirect( home_url('/thank-you') );
        exit;
    }
}
