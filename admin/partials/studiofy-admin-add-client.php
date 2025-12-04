<?php
if ( isset( $_POST['submit_client'] ) ) {
	check_admin_referer( 'studiofy_save_client' );
	global $wpdb;
	$wpdb->insert(
		$wpdb->prefix . 'studiofy_clients',
		array(
			'name'       => sanitize_text_field( $_POST['name'] ?? '' ),
			'email'      => sanitize_email( $_POST['email'] ?? '' ),
			'status'     => 'lead',
			'created_at' => current_time( 'mysql' ),
		)
	);
	echo '<div class="notice notice-success"><p>' . esc_html__( 'Client Added.', 'studiofy-crm' ) . '</p></div>';
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Add Client', 'studiofy-crm' ); ?></h1>
	<form method="post">
		<?php wp_nonce_field( 'studiofy_save_client' ); ?>
		<table class="form-table">
			<tr><th><?php esc_html_e( 'Name', 'studiofy-crm' ); ?></th><td><input type="text" name="name" required class="regular-text"></td></tr>
			<tr><th><?php esc_html_e( 'Email', 'studiofy-crm' ); ?></th><td><input type="email" name="email" required class="regular-text"></td></tr>
		</table>
		<?php submit_button( __( 'Save Client', 'studiofy-crm' ), 'primary', 'submit_client' ); ?>
	</form>
</div>
