<?php
/**
 * Plugin Name: Studiofy CRM
 * Description: A professional Elementor Addon for Photographers. Includes Kanban, Contracts, Proofing Galleries, and Square Invoicing.
 * Version: 2.0.1
 * Author: Ian R. Gordon
 * Author URI: https://iangordon.app
 * Text Domain: studiofy
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Elementor tested up to: 3.25.0
 * @package Studiofy
 * @version 2.0.1
 */

declare(strict_types=1);

namespace Studiofy;

if (!defined('ABSPATH')) {
    exit;
}

// Global Version Control
define('STUDIOFY_VERSION', '2.0.1');
define('STUDIOFY_DB_VERSION', '2.1');
define('STUDIOFY_PATH', plugin_dir_path(__FILE__));
define('STUDIOFY_URL', plugin_dir_url(__FILE__));

/**
 * Smart Versioning Helper
 * Returns file modification time in Dev mode (for instant cache busting)
 * Returns STUDIOFY_VERSION in Production mode (for stability)
 */
function studiofy_get_asset_version(string $file_path): string {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $file = STUDIOFY_PATH . $file_path;
        if (file_exists($file)) {
            return (string) filemtime($file);
        }
    }
    return STUDIOFY_VERSION;
}

// PSR-4 Autoloader
spl_autoload_register(function (string $class) {
    $prefix = 'Studiofy\\';
    $base_dir = STUDIOFY_PATH . 'includes/';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Lifecycle Hooks
register_activation_hook(__FILE__, [Core\Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [Core\Deactivator::class, 'deactivate']);

// Safe Boot
function run_studiofy(): void {
    if (version_compare(PHP_VERSION, '8.1', '<')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>Studiofy CRM requires PHP 8.1+. Please upgrade your server.</p></div>';
        });
        return;
    }
    
    $plugin = new Core\Plugin();
    $plugin->run();

    // Elementor Hook
    add_action('plugins_loaded', function() {
        if (did_action('elementor/loaded')) {
            \Studiofy\Elementor\Addon::instance();
        }
    });
}
run_studiofy();
