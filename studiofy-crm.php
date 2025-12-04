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
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Constants.
define( 'STUDIOFY_VERSION', '1.0.0' );
define( 'STUDIOFY_MIN_PHP', '7.4' ); // Centralized Version Control
define( 'STUDIOFY_PATH', plugin_dir_path( __FILE__ ) );
define( 'STUDIOFY_URL', plugin_dir_url( __FILE__ ) );

/**
 * -------------------------------------------------------------------------
 * STRICT ACTIVATION CHECK (The Rollback Logic)
 * -------------------------------------------------------------------------
 * This runs ONLY when the user clicks "Activate".
 * If PHP is too old, it deactivates self and kills the process with a message.
 */
function studiofy_check_requirements_on_activation() {
	if ( version_compare( PHP_VERSION, STUDIOFY_MIN_PHP, '<' ) ) {
		// 1. Deactivate the plugin immediately (Rollback)
		deactivate_plugins( plugin_basename( __FILE__ ) );

		// 2. Display Error Message and Stop
		$error_message = sprintf(
			/* translators: 1: Required PHP version, 2: Current PHP version */
			esc_html__( 'Studiofy CRM requires PHP %1$s or higher. Your server is running PHP %2$s. The plugin has been deactivated.', 'studiofy-crm' ),
			STUDIOFY_MIN_PHP,
			PHP_VERSION
		);

		// wp_die generates the standard WordPress error screen with a "Back" button
		wp_die(
			$error_message, 
			esc_html__( 'Plugin Activation Error', 'studiofy-crm' ), 
			array( 'back_link' => true ) 
		);
	}
}
register_activation_hook( __FILE__, 'studiofy_check_requirements_on_activation' );

/**
 * -------------------------------------------------------------------------
 * RUNTIME GUARD CLAUSE
 * -------------------------------------------------------------------------
 * This protects the site if the PHP version is downgraded AFTER activation,
 * preventing the "White Screen of Death" (WSOD).
 */
if ( version_compare( PHP_VERSION, STUDIOFY_MIN_PHP, '<' ) ) {
	add_action( 'admin_notices', 'studiofy_php_version_notice' );
	return; // Stop loading the rest of the plugin
}

function studiofy_php_version_notice() {
	$message = sprintf(
		/* translators: 1: Required PHP version, 2: Current PHP version */
		esc_html__( 'Studiofy CRM has paused execution because it requires PHP %1$s+. You are running %2$s.', 'studiofy-crm' ),
		STUDIOFY_MIN_PHP,
		PHP_VERSION
	);
	echo '<div class="notice notice-error"><p>' . $message . '</p></div>';
}

// -------------------------------------------------------------------------
// PLUGIN LOAD
// -------------------------------------------------------------------------

// Autoload Composer Dependencies.
if ( file_exists( STUDIOFY_PATH . 'vendor/autoload.php' ) ) {
	require_once STUDIOFY_PATH . 'vendor/autoload.php';
}

// Include Core Classes.
require_once STUDIOFY_PATH . 'includes/class-studiofy-core.php';
require_once STUDIOFY_PATH . 'includes/class-studiofy-activator.php';
require_once STUDIOFY_PATH . 'includes/class-studiofy-deactivator.php';

/**
 * Begins execution of the plugin.
 */
function run_studiofy_crm() {
	$plugin = new Studiofy_Core();
	$plugin->run();
}
run_studiofy_crm();

// Register Lifecycle Hooks.
register_activation_hook( __FILE__, array( 'Studiofy_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Studiofy_Deactivator', 'deactivate' ) );
