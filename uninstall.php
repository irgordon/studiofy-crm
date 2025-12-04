<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;
$options = get_option( 'studiofy_settings' );
if ( isset( $options['delete_data_on_uninstall'] ) && 'true' === $options['delete_data_on_uninstall'] ) {
	$cpts = array( 'studiofy_project', 'studiofy_lead', 'studiofy_invoice', 'studiofy_contract', 'studiofy_session', 'studiofy_gallery' );
	foreach ( $cpts as $pt ) {
		$posts = get_posts( array( 'post_type' => $pt, 'numberposts' => -1, 'post_status' => 'any' ) );
		foreach ( $posts as $p ) wp_delete_post( $p->ID, true );
	}
	global $wpdb;
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}studiofy_clients" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}studiofy_messages" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}studiofy_entries" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}studiofy_api_logs" );
	delete_option( 'studiofy_settings' );
}
