<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$options = get_option( 'studiofy_settings' );

if ( isset( $options['delete_data_on_uninstall'] ) && 'true' === $options['delete_data_on_uninstall'] ) {
	// Clean up CPTs
	$cpts = array( 'studiofy_project', 'studiofy_lead', 'studiofy_invoice', 'studiofy_contract' );
	foreach ( $cpts as $post_type ) {
		$posts = get_posts(
			array(
				'post_type'   => $post_type,
				'numberposts' => -1,
				'post_status' => 'any',
			)
		);
		foreach ( $posts as $post ) {
			wp_delete_post( $post->ID, true );
		}
	}
	
	// Drop Client Table
	global $wpdb;
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}studiofy_clients" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}studiofy_api_logs" );

	delete_option( 'studiofy_settings' );
}
