<?php
/**
 * Plugin Name: Studiofy CRM
 * Description: High-performance CRM for Photographers with Encrypted Data and Elementor Integration.
 * Version: 2.0.4
 * Author: Ian R. Gordon
 * Text Domain: studiofy
 * Requires PHP: 8.1
 * @package Studiofy
 * @version 2.0.4
 */

declare(strict_types=1);

namespace Studiofy;

if (!defined('ABSPATH')) {
    exit;
}

define('STUDIOFY_VERSION', '2.0.4');
define('STUDIOFY_DB_VERSION', '2.4');
define('STUDIOFY_PATH', plugin_dir_path(__FILE__));
define('STUDIOFY_URL', plugin_dir_url(__FILE__));

// Define Encryption Key (Should be moved to wp-config.php in production)
if (!defined('STUDIOFY_KEY')) {
    define('STUDIOFY_KEY', 'DEF_CHANGE_THIS_IN_WP_CONFIG_TO_RANDOM_32_BYTES');
}

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

// Boot Plugin
function run_studiofy(): void {
    if (version_compare(PHP_VERSION, '8.1', '<')) {
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
