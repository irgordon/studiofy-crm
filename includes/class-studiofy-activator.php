<?php
class Studiofy_Activator {
	public static function activate() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = array();

		// 1. Clients Table.
		$sql[] = "CREATE TABLE {$wpdb->prefix}studiofy_clients (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name tinytext NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20) DEFAULT '' NOT NULL,
            status varchar(50) DEFAULT 'lead', 
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            KEY email (email)
        ) $charset_collate;";

		// 2. Client Meta (EAV).
		$sql[] = "CREATE TABLE {$wpdb->prefix}studiofy_clientmeta (
            meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            studiofy_client_id mediumint(9) NOT NULL,
            meta_key varchar(255) DEFAULT '',
            meta_value longtext,
            PRIMARY KEY  (meta_id),
            KEY client_id (studiofy_client_id),
            KEY meta_key (meta_key(191))
        ) $charset_collate;";

		// 3. Invoices.
		$sql[] = "CREATE TABLE {$wpdb->prefix}studiofy_invoices (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            client_id mediumint(9) NOT NULL,
            square_invoice_id varchar(100),
            invoice_url text,
            amount decimal(10,2) NOT NULL,
            status varchar(50) DEFAULT 'draft',
            notes text,
            due_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

		// 4. Contracts.
		$sql[] = "CREATE TABLE {$wpdb->prefix}studiofy_contracts (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            access_token varchar(64),
            client_id mediumint(9) NOT NULL,
            title text NOT NULL,
            content longtext NOT NULL,
            signature_data longtext DEFAULT '', 
            signed_ip varchar(100) DEFAULT '',
            status varchar(50) DEFAULT 'draft',
            signed_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            KEY access_token (access_token)
        ) $charset_collate;";

		// 5. Bookings.
		$sql[] = "CREATE TABLE {$wpdb->prefix}studiofy_bookings (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            client_id mediumint(9) NOT NULL,
            google_event_id varchar(255) DEFAULT '',
            start_date datetime NOT NULL,
            end_date datetime NOT NULL,
            title text NOT NULL,
            status varchar(50) DEFAULT 'scheduled',
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            KEY client_id (client_id)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		// Add Capabilities.
		$role = get_role( 'administrator' );
		if ( $role ) {
			$role->add_cap( 'view_studiofy_crm' );
			$role->add_cap( 'edit_studiofy_client' );
			$role->add_cap( 'manage_studiofy_settings' );
			$role->add_cap( 'manage_studiofy_invoices' );
			$role->add_cap( 'manage_studiofy_contracts' );
		}

		// Welcome Flag.
		set_transient( 'studiofy_activation_redirect', true, 60 );
	}
}
