<?php
/**
 * Plugin Name: Studiofy CRM
 * Description: A comprehensive Elementor Addon and CRM for Photographers. Optimized for High Performance.
 * Version: 2.2.1
 * Author: Ian R. Gordon
 * Text Domain: studiofy
 * Requires PHP: 8.1
 * Requires at least: 6.6
 * Elementor tested up to: 3.25.0
 * @package Studiofy
 * @version 2.2.1
 */

declare(strict_types=1);

namespace Studiofy;

if (!defined('ABSPATH')) {
    exit;
}

define('STUDIOFY_VERSION', '2.2.1');
define('STUDIOFY_DB_VERSION', '2.11');
define('STUDIOFY_PATH', plugin_dir_path(__FILE__));
define('STUDIOFY_URL', plugin_dir_url(__FILE__));

if (!defined('STUDIOFY_KEY')) {
    define('STUDIOFY_KEY', 'DEF_CHANGE_THIS_IN_WP_CONFIG_TO_RANDOM_32_BYTES');
}

function studiofy_get_asset_version(string $file_path): string {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $file = STUDIOFY_PATH . $file_path;
        if (file_exists($file)) {
            return (string) filemtime($file);
        }
    }
    return STUDIOFY_VERSION;
}

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

register_activation_hook(__FILE__, [Core\Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [Core\Deactivator::class, 'deactivate']);

function studiofy_check_dependencies(): void {
    if (did_action('elementor/loaded')) return;

    add_action('admin_notices', function() {
        $screen = get_current_screen();
        if (isset($screen->parent_file) && 'plugins.php' === $screen->parent_file) return;

        $plugin = 'elementor/elementor.php';
        $installed = get_plugins();
        $is_installed = isset($installed[$plugin]);
        $url = $is_installed 
            ? wp_nonce_url('plugins.php?action=activate&plugin='.$plugin, 'activate-plugin_'.$plugin)
            : wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=elementor'), 'install-plugin_elementor');
        $text = $is_installed ? 'Activate Elementor' : 'Install Elementor Now';

        echo '<div class="notice notice-error"><p><strong>Studiofy CRM</strong> requires <strong>Elementor</strong> for frontend features.</p><p><a href="'.$url.'" class="button button-primary">'.$text.'</a></p></div>';
    });
}
add_action('plugins_loaded', 'Studiofy\\studiofy_check_dependencies');

function run_studiofy(): void {
    if (version_compare(PHP_VERSION, '8.1', '<')) return;
    $plugin = new Core\Plugin();
    $plugin->run();
    add_action('plugins_loaded', function() {
        if (did_action('elementor/loaded')) \Studiofy\Elementor\Addon::instance();
    }, 20);
}
run_studiofy();
