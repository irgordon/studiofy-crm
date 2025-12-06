<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Studiofy
 * @version 2.2.22
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

/**
 * 1. Drop Custom Tables
 */
$tables = [
    'studiofy_customers',
    'studiofy_projects',
    'studiofy_milestones',
    'studiofy_tasks',
    'studiofy_contracts',
    'studiofy_invoices',
    'studiofy_bookings',
    'studiofy_galleries',
    'studiofy_gallery_files',
    'studiofy_gallery_selections'
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}$table");
}

/**
 * 2. Delete Custom Post Types & Associated Pages
 */
// Delete Contract Docs (Elementor CPT)
$contract_posts = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'studiofy_doc'");
foreach ($contract_posts as $post_id) {
    wp_delete_post($post_id, true);
}

// Delete Private Gallery Pages (Identified by title suffix or metadata if possible, 
// strictly we rely on the DB table data which we just dropped, so we try to catch stragglers 
// by content search if the table data is already gone, BUT since we run this logic, 
// we assume the user might have orphaned pages if they didn't use the plugin delete function before.
// However, standard uninstall relies on data logic.
// Best practice: Delete pages linked in the options or search by shortcode presence.)

$gallery_pages = $wpdb->get_results("SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE '%[studiofy_proof_gallery%' AND post_type = 'page'");
foreach ($gallery_pages as $page) {
    wp_delete_post($page->ID, true);
}

/**
 * 3. Delete Filesystem Data
 */
$upload_dir = wp_upload_dir();
$studiofy_dir = $upload_dir['basedir'] . '/studiofy_galleries';

if (is_dir($studiofy_dir)) {
    // Recursive delete function
    $iterator = new RecursiveDirectoryIterator($studiofy_dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);
    
    foreach($files as $file) {
        if ($file->isDir()){
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }
    rmdir($studiofy_dir);
}

/**
 * 4. Cleanup Options & Transients
 */
delete_option('studiofy_branding');
delete_option('studiofy_db_version');
delete_option('studiofy_do_activation_redirect');
delete_option('studiofy_demo_data_ids');

// Clear all transients
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_studiofy_%'");
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_studiofy_%'");
