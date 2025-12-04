<?php
/**
 * Plugin Name:       Studiofy CRM
 * Plugin URI:        https://iangordon.app/studiofy-crm
 * Description:       Professional Studio Management: Projects, Leads, Invoices, Scheduling, and Contracts.
 * Version:           2.0.0
 * Author:            Ian R. Gordon
 * License:           GPL-3.0
 * Text Domain:       studiofy-crm
 * Requires at least: 6.4
 * Requires PHP:      8.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'STUDIOFY_VERSION', '2.0.0' );
define( 'STUDIOFY_MIN_PHP', '8.0' );
define( 'STUDIOFY_PATH', plugin_dir_path( __FILE__ ) );
define( 'STUDIOFY_URL', plugin_dir_url( __FILE__ ) );

// Strict Activation Check
function studiofy_check_requirements_on_activation(): void {
	if ( version_compare( PHP_VERSION, STUDIOFY_MIN_PHP, '<' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( 
            sprintf( esc_html__( 'Studiofy CRM requires PHP %s+. Your server is running %s.', 'studiofy-crm' ), STUDIOFY_MIN_PHP, PHP_VERSION ), 
            'Plugin Activation Error', 
            array( 'back_link' => true ) 
        );
	}
}
register_activation_hook( __FILE__, 'studiofy_check_requirements_on_activation' );

if ( version_compare( PHP_VERSION, STUDIOFY_MIN_PHP, '<' ) ) return;

// Autoload
if ( file_exists( STUDIOFY_PATH . 'vendor/autoload.php' ) ) require_once STUDIOFY_PATH . 'vendor/autoload.php';

// Core Includes
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
