<?php
/**
 * Plugin Name:       Studiofy CRM
 * Plugin URI:        https://iangordon.app/studiofy-crm
 * Description:       Complete Studio Management: Projects, Galleries, Contracts, Invoicing, and Custom Forms.
 * Version:           3.1.0
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

define( 'STUDIOFY_VERSION', '3.1.0' );
define( 'STUDIOFY_PATH', plugin_dir_path( __FILE__ ) );
define( 'STUDIOFY_URL', plugin_dir_url( __FILE__ ) );

// Core Includes.
require_once STUDIOFY_PATH . 'includes/class-studiofy-core.php';
require_once STUDIOFY_PATH . 'includes/class-studiofy-activator.php';
require_once STUDIOFY_PATH . 'includes/class-studiofy-deactivator.php';

/**
 * Execution Wrapper
 */
function run_studiofy_crm(): void {
	$plugin = new Studiofy_Core();
	$plugin->run();
}
run_studiofy_crm();

/**
 * Silent Activation Wrapper
 * Prevents "Unexpected Output" errors caused by other plugins throwing warnings.
 */
function studiofy_activate_silently() {
    // Turn on output buffering to catch any stray spaces or warnings
    ob_start();
    
    // Run the actual activation logic
    Studiofy_Activator::activate();
    
    // Discard whatever was output (suppressing errors)
    ob_end_clean();
}

register_activation_hook( __FILE__, 'studiofy_activate_silently' );
register_deactivation_hook( __FILE__, array( 'Studiofy_Deactivator', 'deactivate' ) );
