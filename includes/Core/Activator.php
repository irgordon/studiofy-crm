<?php
/**
 * Activator
 * @package Studiofy\Core
 * @version 2.0.7
 */

declare(strict_types=1);

namespace Studiofy\Core;

class Activator {
    public static function activate(): void {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();

        $tables = [
            'studiofy_customers' => "CREATE TABLE {$wpdb->prefix}studiofy_customers (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                status varchar(20) DEFAULT 'Lead' NOT NULL,
                first_name varchar(100) NOT NULL,
                last_name varchar(100) NOT NULL,
                email varchar(100) NOT NULL,
                phone text NULL,
                company varchar(150) NULL,
                address text NULL,
                notes longtext NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY (id),
                KEY email (email)
            ) $charset_collate;",
            
            'studiofy_invoices' => "CREATE TABLE {$wpdb->prefix}studiofy_invoices (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                invoice_number varchar(50) NULL,
                customer_id mediumint(9) NOT NULL,
                project_id mediumint(9) NULL,
                title varchar(255) NOT NULL,
                amount decimal(10,2) NOT NULL,
                tax_amount decimal(10,2) DEFAULT 0.00,
                line_items longtext NULL,
                issue_date date NULL,
                due_date date NOT NULL,
                status varchar(20) DEFAULT 'Draft' NOT NULL,
                payment_link text NULL,
                external_id varchar(100) NULL,
                order_id varchar(100) NULL,
                currency varchar(3) DEFAULT 'USD',
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;",

            'studiofy_projects' => "CREATE TABLE {$wpdb->prefix}studiofy_projects (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                customer_id mediumint(9) NOT NULL,
                title varchar(255) NOT NULL,
                status varchar(50) DEFAULT 'todo' NOT NULL,
                budget decimal(10,2) NULL,
                notes longtext NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;",

            'studiofy_contracts' => "CREATE TABLE {$wpdb->prefix}studiofy_contracts (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                customer_id mediumint(9) NOT NULL,
                project_id mediumint(9) NULL,
                title varchar(255) NOT NULL,
                amount decimal(10,2) DEFAULT 0.00,
                start_date date NULL,
                end_date date NULL,
                body_content longtext NOT NULL,
                signature_data longtext NULL,
                signed_name varchar(100) NULL,
                status varchar(20) DEFAULT 'draft' NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;",

            'studiofy_bookings' => "CREATE TABLE {$wpdb->prefix}studiofy_bookings (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                customer_id mediumint(9) NULL,
                guest_name varchar(100) NULL,
                guest_email varchar(100) NULL,
                title varchar(255) NOT NULL,
                location varchar(255) NULL,
                notes longtext NULL,
                booking_date date NOT NULL,
                booking_time time NOT NULL,
                status varchar(20) DEFAULT 'Scheduled' NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY (id),
                KEY date_time (booking_date, booking_time)
            ) $charset_collate;",

            'studiofy_milestones' => "CREATE TABLE {$wpdb->prefix}studiofy_milestones (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                project_id mediumint(9) NOT NULL,
                name varchar(255) NOT NULL,
                is_completed boolean DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;",

            'studiofy_tasks' => "CREATE TABLE {$wpdb->prefix}studiofy_tasks (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                milestone_id mediumint(9) NOT NULL,
                title varchar(255) NOT NULL,
                checklist_json longtext NULL,
                status varchar(20) DEFAULT 'pending',
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;",

            'studiofy_gallery_selections' => "CREATE TABLE {$wpdb->prefix}studiofy_gallery_selections (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                gallery_id bigint(20) UNSIGNED NOT NULL,
                attachment_id bigint(20) UNSIGNED NOT NULL,
                status varchar(20) DEFAULT 'selected' NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;"
        ];

        foreach ($tables as $sql) {
            dbDelta($sql);
        }
        
        if(!get_option('studiofy_db_version')) {
            add_option('studiofy_do_activation_redirect', true);
        }
        update_option('studiofy_db_version', STUDIOFY_DB_VERSION);
    }
}
