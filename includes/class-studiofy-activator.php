<?php
/**
 * Fired during plugin activation.
 *
 * @package    Studiofy_CRM
 * @subpackage Studiofy_CRM/includes
 * @version    5.1.1
 */

declare( strict_types=1 );

class Studiofy_Activator {

	/**
	 * Create or update database tables.
	 * Uses dbDelta which requires very specific SQL formatting.
	 */
	public static function activate(): void {
		global $wpdb;
		
		$charset_collate = $wpdb->get_charset_collate();
		
		// Table Names
		$table_clients  = $wpdb->prefix . 'studiofy_clients';
		$table_messages = $wpdb->prefix . 'studiofy_messages';
		$table_entries  = $wpdb->prefix . 'studiofy_entries';
		$table_logs     = $wpdb->prefix . 'studiofy_api_logs';

		$sql = array();

		// 1. Clients Table
		// NOTE: 2 spaces after PRIMARY KEY is required by dbDelta
		$sql[] = "CREATE TABLE $table_clients (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name tinytext NOT NULL,
			email varchar(100) NOT NULL,
			phone varchar(20) DEFAULT '' NOT NULL,
			status varchar(50) DEFAULT 'lead' NOT NULL,
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		// 2. Messages Table
		$sql[] = "CREATE TABLE $table_messages (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			client_id mediumint(9) NOT NULL,
			direction varchar(10) NOT NULL,
			subject text NOT NULL,
			message longtext NOT NULL,
			sent_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id),
			KEY client_id (client_id)
		) $charset_collate;";

		// 3. Entries Table
		$sql[] = "CREATE TABLE $table_entries (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			form_id bigint(20) NOT NULL,
			entry_data longtext NOT NULL,
			source_url varchar(255) DEFAULT '' NOT NULL,
			ip_address varchar(50) DEFAULT '' NOT NULL,
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id),
			KEY form_id (form_id)
		) $charset_collate;";

		// 4. API Logs Table
		$sql[] = "CREATE TABLE $table_logs (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			service varchar(50) NOT NULL,
			endpoint varchar(255) NOT NULL,
			response_code smallint(4) NOT NULL,
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		// Register Capabilities
		$role = get_role( 'administrator' );
		if ( $role instanceof WP_Role ) {
			$role->add_cap( 'manage_studiofy_crm' );
		}
		
		// Clear permalinks for CPTs
		flush_rewrite_rules();
	}
}
