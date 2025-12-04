<?php
/**
 * Plugin Name:       Studiofy CRM
 * Plugin URI:        https://iangordon.app/studiofy-crm
 * Description:       Complete Studio Management: Projects, Galleries, Contracts, Invoicing, and Custom Forms.
 * Version:           5.3.0
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

// Define Constants
define( 'STUDIOFY_VERSION', '5.3.0' );
define( 'STUDIOFY_PATH', plugin_dir_path( __FILE__ ) );
define( 'STUDIOFY_URL', plugin_dir_url( __FILE__ ) );

// Core Includes
require_once STUDIOFY_PATH . 'includes/class-studiofy-core.php';
require_once STUDIOFY_PATH . 'includes/class-studiofy-activator.php';
require_once STUDIOFY_PATH . 'includes/class-studiofy-deactivator.php';

/**
 * Main execution function.
 */
function run_studiofy_crm(): void {
	$plugin = new Studiofy_Core();
	$plugin->run();
}
run_studiofy_crm();

/**
 * Silent Activation Wrapper
 * * This captures and discards any "Unexpected Output" (white space or PHP warnings 
 * from other plugins) that might occur during the activation process, preventing 
 * the "Headers already sent" error.
 */
function studiofy_activate_silently(): void {
	// Start output buffering
	ob_start();
	
	try {
		// Run the actual database creation logic
		Studiofy_Activator::activate();
	} catch ( Throwable $e ) {
		// Log errors silently to debug.log instead of crashing the screen
		error_log( 'Studiofy Activation Error: ' . $e->getMessage() );
	}
	
	// Discard whatever was printed to the screen
	ob_end_clean();
}

/**
 * Register Hooks
 * Note: We pass the function name string for the silent activator
 */
register_activation_hook( __FILE__, 'studiofy_activate_silently' );
register_deactivation_hook( __FILE__, array( 'Studiofy_Deactivator', 'deactivate' ) );
