<?php
/**
 * Uninstall Logic
 * @package Studiofy
 * @version 2.0.5
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$tables = [
    'studiofy_customers', // Renamed from clients
    'studiofy_projects',
    'studiofy_milestones',
    'studiofy_tasks',
    'studiofy_contracts',
    'studiofy_invoices',
    'studiofy_gallery_selections',
    'studiofy_bookings'
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}$table");
}

delete_option('studiofy_branding');
delete_option('studiofy_db_version');
delete_option('studiofy_do_activation_redirect');

$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_studiofy_%'");
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_studiofy_%'");

$terms = get_terms(['taxonomy' => 'studiofy_folder', 'hide_empty' => false]);
if (!is_wp_error($terms)) {
    foreach ($terms as $term) {
        wp_delete_term($term->term_id, 'studiofy_folder');
    }
}
