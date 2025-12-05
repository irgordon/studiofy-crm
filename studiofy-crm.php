<?php
/**
 * Plugin Name: Studiofy CRM
 * Description: A professional Elementor Addon for Photographers.
 * Version: 2.0.0
 * Author: Ian R. Gordon
 * Text Domain: studiofy
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Elementor tested up to: 3.25.0
 * @package Studiofy
 * @version 2.0.0
 */

declare(strict_types=1);

namespace Studiofy;

if (!defined('ABSPATH')) exit;

define('STUDIOFY_VERSION', '2.0.0');
define('STUDIOFY_DB_VERSION', '2.0');
define('STUDIOFY_PATH', plugin_dir_path(__FILE__));
define('STUDIOFY_URL', plugin_dir_url(__FILE__));

/**
 * Versioning Helper
 * returns filemtime in debug mode, or STUDIOFY_VERSION in prod.
 */
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
