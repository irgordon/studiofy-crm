<?php
class Studiofy_Settings {
	public function __construct() {
		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'menu' ) );
	}
	public function menu() { add_submenu_page( 'studiofy-dashboard', 'Settings', 'Settings', 'manage_options', 'studiofy-settings', array( $this, 'render' ) ); }
	public function init() {
		register_setting( 'studiofy_options', 'studiofy_settings', array($this,'sanitize') );
		add_settings_section( 'api', 'API Keys', null, 'studiofy-settings' );
		add_settings_field( 'square_token', 'Square Token', array($this,'field'), 'studiofy-settings', 'api' );
	}
	public function sanitize($i) { return $i; } // Add encryption logic here
	public function field() { echo '<input type="password" name="studiofy_settings[square_token]" class="regular-text">'; }
	public function render() { echo '<div class="wrap"><h1>Settings</h1><form method="post" action="options.php">'; settings_fields('studiofy_options'); do_settings_sections('studiofy-settings'); submit_button(); echo '</form></div>'; }
}
