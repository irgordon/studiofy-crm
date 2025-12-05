<?php
declare(strict_types=1);
namespace Studiofy\Core;

class Activator {
    public static function activate(): void {
        if (!current_user_can('activate_plugins')) return;
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();

        $tables = [
            'studiofy_clients' => "CREATE TABLE {$wpdb->prefix}studiofy_clients (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                status varchar(20) DEFAULT 'lead' NOT NULL,
                first_name varchar(100) NOT NULL,
                last_name varchar(100) NOT NULL,
                email varchar(100) NOT NULL,
                phone varchar(50) NULL,
                social_media text NULL,
                custom_field_1 text NULL,
                custom_field_2 text NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY (id), KEY email (email)
            ) $charset_collate;",
            
            'studiofy_bookings' => "CREATE TABLE {$wpdb->prefix}studiofy_bookings (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                guest_name varchar(100) NULL,
                guest_email varchar(100) NULL,
                service_type varchar(100) NOT NULL,
                booking_date date NOT NULL,
                booking_time time NOT NULL,
                status varchar(20) DEFAULT 'pending' NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY (id), KEY date_time (booking_date, booking_time)
            ) $charset_collate;",

            'studiofy_projects' => "CREATE TABLE {$wpdb->prefix}studiofy_projects (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                client_id mediumint(9) NOT NULL,
                title varchar(255) NOT NULL,
                status varchar(50) DEFAULT 'todo' NOT NULL,
                budget decimal(10,2) NULL,
                notes longtext NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;",
            
            // ... (Tables for milestones, tasks, contracts, invoices, gallery_selections are assumed identical to v1.0, just ensure they are included here) ...
             'studiofy_milestones' => "CREATE TABLE {$wpdb->prefix}studiofy_milestones (id mediumint(9) NOT NULL AUTO_INCREMENT, project_id mediumint(9) NOT NULL, name varchar(255) NOT NULL, is_completed boolean DEFAULT 0, created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY (id)) $charset_collate;",
             'studiofy_tasks' => "CREATE TABLE {$wpdb->prefix}studiofy_tasks (id mediumint(9) NOT NULL AUTO_INCREMENT, milestone_id mediumint(9) NOT NULL, title varchar(255) NOT NULL, checklist_json longtext NULL, status varchar(20) DEFAULT 'pending', created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY (id)) $charset_collate;",
             'studiofy_contracts' => "CREATE TABLE {$wpdb->prefix}studiofy_contracts (id mediumint(9) NOT NULL AUTO_INCREMENT, client_id mediumint(9) NOT NULL, project_id mediumint(9) NOT NULL, title varchar(255) NOT NULL, amount decimal(10,2) DEFAULT 0.00, body_content longtext NOT NULL, signature_data longtext NULL, signed_name varchar(100) NULL, status varchar(20) DEFAULT 'draft' NOT NULL, created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY (id)) $charset_collate;",
             'studiofy_invoices' => "CREATE TABLE {$wpdb->prefix}studiofy_invoices (id mediumint(9) NOT NULL AUTO_INCREMENT, external_id varchar(100) NULL, order_id varchar(100) NULL, title varchar(255) NOT NULL, amount decimal(10,2) NOT NULL, currency varchar(3) DEFAULT 'USD', due_date date NOT NULL, status varchar(20) DEFAULT 'DRAFT' NOT NULL, payment_link text NULL, created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY (id)) $charset_collate;",
             'studiofy_gallery_selections' => "CREATE TABLE {$wpdb->prefix}studiofy_gallery_selections (id mediumint(9) NOT NULL AUTO_INCREMENT, gallery_id bigint(20) UNSIGNED NOT NULL, attachment_id bigint(20) UNSIGNED NOT NULL, status varchar(20) DEFAULT 'selected' NOT NULL, created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY (id)) $charset_collate;"
        ];

        foreach ($tables as $sql) dbDelta($sql);

        add_option('studiofy_do_activation_redirect', true);
        add_option('studiofy_db_version', STUDIOFY_DB_VERSION);
    }
}
