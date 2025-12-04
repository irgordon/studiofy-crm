<?php
declare( strict_types=1 );

class Studiofy_Activator {
	public static function activate(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		
		// 1. Clients Table (Hybrid approach: CPTs for logic, Table for Clients list performance)
		$table_clients = $wpdb->prefix . 'studiofy_clients';
		$sql_clients   = "CREATE TABLE $table_clients (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name tinytext NOT NULL,
			email varchar(100) NOT NULL,
			phone varchar(20) DEFAULT '',
			status varchar(50) DEFAULT 'lead',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;";

		// 2. API Logs
		$table_logs = $wpdb->prefix . 'studiofy_api_logs';
		$sql_logs   = "CREATE TABLE $table_logs (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			service varchar(50) NOT NULL,
			endpoint varchar(255) NOT NULL,
			response_code smallint(4) NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_clients );
		dbDelta( $sql_logs );

		// Capabilities
		$role = get_role( 'administrator' );
		if ( $role instanceof WP_Role ) {
			$role->add_cap( 'manage_studiofy_crm' );
		}
		
		flush_rewrite_rules();
	}
}
