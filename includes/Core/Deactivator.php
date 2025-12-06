<?php
/**
 * Deactivator
 * @package Studiofy\Core
 * @version 2.2.16
 */

declare(strict_types=1);

namespace Studiofy\Core;

class Deactivator {
    
    public static function deactivate(): void {
        global $wpdb;

        // 1. Delete Private Gallery Pages
        $pages = $wpdb->get_col("SELECT wp_page_id FROM {$wpdb->prefix}studiofy_galleries WHERE wp_page_id > 0");
        foreach ($pages as $page_id) {
            wp_delete_post($page_id, true); // Force delete
        }

        // 2. Clean up Filesystem
        $upload = wp_upload_dir();
        $base_dir = $upload['basedir'] . '/studiofy_galleries';
        self::recursive_rmdir($base_dir);

        // 3. Clean up Options & Transients
        delete_option('studiofy_branding');
        delete_option('studiofy_db_version');
        delete_option('studiofy_do_activation_redirect');
        delete_option('studiofy_demo_data_ids');
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_studiofy_%'");

        // 4. Note: Tables are NOT dropped on Deactivation (WordPress Standard), 
        // only on Uninstall (uninstall.php). Deactivation should preserve data if re-activated.
        // If the requirement "Upon Deactivating... delete" is strict, uncomment below:
        /*
        $tables = ['studiofy_customers','studiofy_projects','studiofy_milestones','studiofy_tasks','studiofy_contracts','studiofy_invoices','studiofy_gallery_selections','studiofy_bookings','studiofy_galleries','studiofy_gallery_files'];
        foreach ($tables as $table) $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}$table");
        */

        flush_rewrite_rules();
    }

    private static function recursive_rmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . DIRECTORY_SEPARATOR . $object))
                        self::recursive_rmdir($dir . DIRECTORY_SEPARATOR . $object);
                    else
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
            rmdir($dir);
        }
    }
}
