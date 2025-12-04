<?php
declare( strict_types=1 );
class Studiofy_Activator {
	public static function activate(): void {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$sql = array();
		$sql[] = "CREATE TABLE {$wpdb->prefix}studiofy_clients (id mediumint(9) NOT NULL AUTO_INCREMENT, name tinytext NOT NULL, email varchar(100) NOT NULL, phone varchar(20), address text, status varchar(50) DEFAULT 'lead', created_at datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id)) $charset;";
		$sql[] = "CREATE TABLE {$wpdb->prefix}studiofy_entries (id bigint(20) unsigned NOT NULL AUTO_INCREMENT, form_id bigint(20) NOT NULL, entry_data longtext, source_url varchar(255), created_at datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), KEY form_id (form_id)) $charset;";
		$sql[] = "CREATE TABLE {$wpdb->prefix}studiofy_messages (id bigint(20) unsigned NOT NULL AUTO_INCREMENT, client_id mediumint(9) NOT NULL, direction varchar(10), subject text, message longtext, sent_at datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id)) $charset;";
		$sql[] = "CREATE TABLE {$wpdb->prefix}studiofy_api_logs (id bigint(20) unsigned NOT NULL AUTO_INCREMENT, service varchar(50), endpoint varchar(255), response_code smallint(4), created_at datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id)) $charset;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $q ) dbDelta( $q );
		flush_rewrite_rules();
	}
}
