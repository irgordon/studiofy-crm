<?php
/**
 * Plugin Name:       Studiofy CRM
 * Plugin URI:        https://iangordon.app/studiofycrm
 * Description:       A professional CRM for Photographers. Manages clients, bookings, invoices (Square), and contracts (Digital Signature).
 * Version:           1.0.0
 * Author:            Ian R. Gordon
 * License:           GPL-3.0
 * Text Domain:       studiofy-crm
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'STUDIOFY_VERSION', '1.0.0' );
define( 'STUDIOFY_PATH', plugin_dir_path( __FILE__ ) );
define( 'STUDIOFY_URL', plugin_dir_url( __FILE__ ) );

// Autoload Composer Dependencies
if ( file_exists( STUDIOFY_PATH . 'vendor/autoload.php' ) ) {
    require_once STUDIOFY_PATH . 'vendor/autoload.php';
}

// Include Core Classes
require_once STUDIOFY_PATH . 'includes/class-studiofy-core.php';
require_once STUDIOFY_PATH . 'includes/class-studiofy-activator.php';
require_once STUDIOFY_PATH . 'includes/class-studiofy-deactivator.php';

function run_studiofy_crm() {
    $plugin = new Studiofy_Core();
    $plugin->run();
}
run_studiofy_crm();

register_activation_hook( __FILE__, array( 'Studiofy_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Studiofy_Deactivator', 'deactivate' ) );
