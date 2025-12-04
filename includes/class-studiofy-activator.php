<?php
declare( strict_types=1 );

class Studiofy_Activator {
	public static function activate(): void {
		global $wpdb;
		
		$charset_collate = $wpdb->get_charset_collate();
		
		// 1. Clients Table
		// FIX: Reordered columns and fixed PRIMARY KEY spacing for dbDelta
		$table_clients = $wpdb->prefix . 'studiofy_clients';
		$sql_clients   = "CREATE TABLE $table_clients (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name tinytext NOT NULL,
			email varchar(100) NOT NULL,
			phone varchar(20) DEFAULT '' NOT NULL,
			status varchar(50) DEFAULT 'lead' NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		// 2. Messages Table
		$table_messages = $wpdb->prefix . 'studiofy_messages';
		$sql_messages   = "CREATE TABLE $table_messages (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			client_id mediumint(9) NOT NULL,
			direction varchar(10) NOT NULL,
			subject text NOT NULL,
			message longtext NOT NULL,
			sent_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY client_id (client_id)
		) $charset_collate;";

		// 3. Entries Table
		$table_entries = $wpdb->prefix . 'studiofy_entries';
		$sql_entries   = "CREATE TABLE $table_entries (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			form_id bigint(20) NOT NULL,
			entry_data longtext NOT NULL,
			source_url varchar(255) DEFAULT '' NOT NULL,
			ip_address varchar(50) DEFAULT '' NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY form_id (form_id)
		) $charset_collate;";

		// 4. API Logs Table
		$table_logs = $wpdb->prefix . 'studiofy_api_logs';
		$sql_logs   = "CREATE TABLE $table_logs (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			service varchar(50) NOT NULL,
			endpoint varchar(255) NOT NULL,
			response_code smallint(4) NOT NULL,
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		
		dbDelta( $sql_clients );
		dbDelta( $sql_messages );
		dbDelta( $sql_entries );
		dbDelta( $sql_logs );

		// Register Capabilities
		$role = get_role( 'administrator' );
		if ( $role instanceof WP_Role ) {
			$role->add_cap( 'manage_studiofy_crm' );
		}
		
		flush_rewrite_rules();
	}
}
