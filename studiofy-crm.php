<?php
/**
 * Plugin Name:       Studiofy CRM
 * Plugin URI:        https://iangordon.app/studiofy-crm
 * Description:       A professional CRM for Photographers. Manages clients, bookings, invoices, and contracts.
 * Version:           1.0.1
 * Author:            Ian R. Gordon
 * License:           GPL-3.0
 * Text Domain:       studiofy-crm
 * Domain Path:       /languages
 * Requires at least: 6.4
 * Requires PHP:      8.0
 */

declare(strict_types=1);

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Constants.
define( 'STUDIOFY_VERSION', '1.0.0' );
define( 'STUDIOFY_MIN_PHP', '8.0' );
define( 'STUDIOFY_PATH', plugin_dir_path( __FILE__ ) );
define( 'STUDIOFY_URL', plugin_dir_url( __FILE__ ) );

/**
 * -------------------------------------------------------------------------
 * STRICT ACTIVATION CHECK (Rollback Logic)
 * -------------------------------------------------------------------------
 */
function studiofy_check_requirements_on_activation(): void {
	if ( version_compare( PHP_VERSION, STUDIOFY_MIN_PHP, '<' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		$error_message = sprintf(
			/* translators: 1: Required PHP version, 2: Current PHP version */
			esc_html__( 'Studiofy CRM requires PHP %1$s or higher. Your server is running PHP %2$s. The plugin has been deactivated.', 'studiofy-crm' ),
			STUDIOFY_MIN_PHP,
			PHP_VERSION
		);
		wp_die( $error_message, esc_html__( 'Plugin Activation Error', 'studiofy-crm' ), array( 'back_link' => true ) );
	}
}
register_activation_hook( __FILE__, 'studiofy_check_requirements_on_activation' );

// Runtime Guard.
if ( version_compare( PHP_VERSION, STUDIOFY_MIN_PHP, '<' ) ) {
	return;
}

// Autoload Composer Dependencies.
if ( file_exists( STUDIOFY_PATH . 'vendor/autoload.php' ) ) {
	require_once STUDIOFY_PATH . 'vendor/autoload.php';
}

// Include Core Classes.
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
