<?php
// Simple form partial
if ( isset( $_POST['submit_client'] ) ) {
    check_admin_referer( 'studiofy_save_client' );
    global $wpdb;
    $wpdb->insert( $wpdb->prefix.'studiofy_clients', [
        'name' => sanitize_text_field($_POST['name']),
        'email' => sanitize_email($_POST['email']),
        'status' => 'lead',
        'created_at' => current_time('mysql')
    ]);
    echo '<div class="notice notice-success"><p>Client Added.</p></div>';
}
?>
<div class="wrap">
    <h1>Add Client</h1>
    <form method="post">
        <?php wp_nonce_field('studiofy_save_client'); ?>
        <table class="form-table">
            <tr><th>Name</th><td><input type="text" name="name" required class="regular-text"></td></tr>
            <tr><th>Email</th><td><input type="email" name="email" required class="regular-text"></td></tr>
        </table>
        <?php submit_button('submit_client'); ?>
    </form>
</div>
