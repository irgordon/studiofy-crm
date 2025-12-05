<?php
/**
 * Admin Menu Controller
 * @package Studiofy\Admin
 * @version 2.0.0
 */

declare(strict_types=1);

namespace Studiofy\Admin;

use function Studiofy\studiofy_get_asset_version;

class Menu {
    // ... (Properties and Init logic same as before) ...

    public function enqueue_styles($hook): void {
        if (strpos($hook, 'studiofy') === false) return;

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_media();

        // Strict Versioning
        wp_enqueue_style('studiofy-admin-css', STUDIOFY_URL . 'assets/css/admin.css', ['wp-color-picker'], studiofy_get_asset_version('assets/css/admin.css'));
        wp_enqueue_script('studiofy-admin-js', STUDIOFY_URL . 'assets/js/admin.js', ['jquery', 'wp-color-picker'], studiofy_get_asset_version('assets/js/admin.js'), true);
    }
    
    // ... (Rest of class) ...
}
