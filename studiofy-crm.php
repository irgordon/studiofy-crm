<?php
/**
 * Plugin Name:       Studiofy CRM
 * Plugin URI:        https://iangordon.app/studiofy-crm
 * Description:       Native CRM for Photographers using Custom Post Types and WP HTTP API.
 * Version:           3.0.0
 * Author:            Ian R. Gordon
 * License:           GPL-3.0
 * Text Domain:       studiofy-crm
 * Requires at least: 6.4
 * Requires PHP:      8.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'STUDIOFY_VERSION', '3.0.0' );
define( 'STUDIOFY_PATH', plugin_dir_path( __FILE__ ) );
define( 'STUDIOFY_URL', plugin_dir_url( __FILE__ ) );

// Core Includes.
require_once STUDIOFY_PATH . 'includes/class-studiofy-core.php';
require_once STUDIOFY_PATH . 'includes/class-studiofy-activator.php';
require_once STUDIOFY_PATH . 'includes/class-studiofy-deactivator.php';

function run_studiofy_crm(): void {
	$plugin = new Studiofy_Core();
	$plugin->run();
}
run_studiofy_crm();

register_activation_hook( __FILE__, array( 'Studiofy_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Studiofy_Deactivator', 'deactivate' ) );
