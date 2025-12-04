<?php
declare(strict_types=1);

class Studiofy_Activator {
    public static function activate(): void {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = array();

        // 1. Projects (Workflow Hub)
        $sql[] = "CREATE TABLE {$wpdb->prefix}studiofy_projects (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            client_id mediumint(9) NOT NULL,
            title text NOT NULL,
            workflow_phase varchar(50) DEFAULT 'New',
            status varchar(50) DEFAULT 'New',
            start_date date,
            end_date date,
            notes longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY client_id (client_id)
        ) $charset_collate;";

        // 2. Leads (Intake)
        $sql[] = "CREATE TABLE {$wpdb->prefix}studiofy_leads (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            first_name tinytext NOT NULL,
            last_name tinytext NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20),
            event_date date,
            notes text,
            status varchar(20) DEFAULT 'new',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // 3. Invoices (Expanded)
        $sql[] = "CREATE TABLE {$wpdb->prefix}studiofy_invoices (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            project_id mediumint(9) DEFAULT 0,
            client_id mediumint(9) NOT NULL,
            invoice_number varchar(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            tax_rate decimal(5,2) DEFAULT 0.00,
            payment_type varchar(50),
            status varchar(50) DEFAULT 'Unpaid',
            due_date date,
            recipient_data longtext,
            square_invoice_id varchar(100),
            invoice_url text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // 4. Clients
        $sql[] = "CREATE TABLE {$wpdb->prefix}studiofy_clients (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name tinytext NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20) DEFAULT '',
            status varchar(50) DEFAULT 'lead',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // 5. Contracts & Bookings (Standard)
        $sql[] = "CREATE TABLE {$wpdb->prefix}studiofy_contracts (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            access_token varchar(64),
            client_id mediumint(9) NOT NULL,
            title text NOT NULL,
            content longtext NOT NULL,
            signature_data longtext, 
            status varchar(50) DEFAULT 'draft',
            signed_at datetime,
            signed_ip varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ( $sql as $query ) dbDelta( $query );

        // Roles
        $role = get_role( 'administrator' );
        if ( $role instanceof WP_Role ) {
            $role->add_cap( 'view_studiofy_crm' );
            $role->add_cap( 'edit_studiofy_client' );
            $role->add_cap( 'manage_studiofy_invoices' );
            $role->add_cap( 'manage_studiofy_settings' );
        }
    }
}
