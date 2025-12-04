<?php
declare( strict_types=1 );
class Studiofy_Forms_Engine {
	public function process_submission(): void {
		if ( ! isset( $_POST['studiofy_nonce'] ) || ! wp_verify_nonce( $_POST['studiofy_nonce'], 'studiofy_form_submit' ) ) wp_die('Security Check Failed');
		global $wpdb;
		$entry = array();
		foreach ( $_POST as $key => $val ) {
			if ( strpos( $key, 'input_' ) === 0 ) $entry[ str_replace( 'input_', '', $key ) ] = sanitize_text_field( $val );
		}
		$wpdb->insert( $wpdb->prefix . 'studiofy_entries', array(
			'form_id' => intval($_POST['form_id']), 'entry_data' => wp_json_encode($entry), 'source_url' => wp_get_referer()
		));
		wp_redirect( add_query_arg( 'status', 'success', wp_get_referer() ) );
		exit;
	}
}
