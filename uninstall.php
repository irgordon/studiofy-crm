<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$options = get_option( 'studiofy_settings' );

// Security: Only delete if user explicitly opted in via settings.
if ( isset( $options['delete_data_on_uninstall'] ) && 'true' === $options['delete_data_on_uninstall'] ) {
	global $wpdb;
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}studiofy_clients" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}studiofy_clientmeta" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}studiofy_bookings" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}studiofy_invoices" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}studiofy_contracts" );

	delete_option( 'studiofy_crm_version' );
	delete_option( 'studiofy_settings' );
}
