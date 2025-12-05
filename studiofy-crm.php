<?php
/**
 * Plugin Name: Studiofy CRM
 * Description: High-performance CRM for Photographers with Encrypted Data and Elementor Integration.
 * Version: 2.0.3
 * Author: Ian R. Gordon
 * Text Domain: studiofy
 * Requires PHP: 8.1
 * @package Studiofy
 */

declare(strict_types=1);

namespace Studiofy;

if (!defined('ABSPATH')) exit;

define('STUDIOFY_VERSION', '2.0.3');
define('STUDIOFY_DB_VERSION', '2.3');
define('STUDIOFY_PATH', plugin_dir_path(__FILE__));
define('STUDIOFY_URL', plugin_dir_url(__FILE__));
define('STUDIOFY_KEY', 'DEF_CHANGE_THIS_IN_WP_CONFIG_TO_RANDOM_32_BYTES'); // Fallback key

function studiofy_get_asset_version(string $file_path): string {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $file = STUDIOFY_PATH . $file_path;
        return file_exists($file) ? (string) filemtime($file) : STUDIOFY_VERSION;
    }
    return STUDIOFY_VERSION;
}

spl_autoload_register(function (string $class) {
    $prefix = 'Studiofy\\';
    $base_dir = STUDIOFY_PATH . 'includes/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

register_activation_hook(__FILE__, [Core\Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [Core\Deactivator::class, 'deactivate']);

function run_studiofy(): void {
    if (version_compare(PHP_VERSION, '8.1', '<')) return;
    $plugin = new Core\Plugin();
    $plugin->run();
    add_action('plugins_loaded', function() {
        if (did_action('elementor/loaded')) {
            \Studiofy\Elementor\Addon::instance();
        }
    });
}
run_studiofy();
