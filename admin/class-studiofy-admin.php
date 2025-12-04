<?php
declare( strict_types=1 );

class Studiofy_Admin {
	private string $plugin_name;
	private string $version;

	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	public function enqueue_scripts( string $hook ): void {
		global $post;
		// Media Uploader
		if ( $post && 'studiofy_gallery' === $post->post_type ) wp_enqueue_media();

		// Only load on Studiofy pages
		// Fix: Check for both 'studiofy' AND specific post types in hook
		if ( false === strpos( $hook, 'studiofy' ) && false === strpos( $hook, 'post.php' ) ) {
			return;
		}

		wp_enqueue_style( $this->plugin_name, STUDIOFY_URL . 'admin/css/studiofy-admin.css', array(), $this->version );
		wp_enqueue_script( $this->plugin_name . '-admin', STUDIOFY_URL . 'admin/js/studiofy-admin.js', array( 'jquery' ), $this->version, true );
		
		if ( $post && 'studiofy_session' === $post->post_type ) {
			wp_enqueue_script( $this->plugin_name . '-builder', STUDIOFY_URL . 'admin/js/studiofy-form-builder.js', array( 'jquery', 'jquery-ui-sortable' ), $this->version, true );
		}
	}

	public function add_plugin_admin_menu(): void {
		// 1. Main Dashboard (Parent)
		add_menu_page( 
			'Studiofy', 
			'Studiofy', 
			'manage_options', 
			'studiofy-dashboard', 
			array( $this, 'render_dashboard' ), 
			'dashicons-camera', 
			6 
		);

		// 2. Settings Submenu
		// Note: The URL generated will be admin.php?page=studiofy-settings
		add_submenu_page( 
			'studiofy-dashboard', 
			'Settings', 
			'Settings', 
			'manage_options', 
			'studiofy-settings', 
			array( $this, 'render_settings' ) 
		);
	}

	public function render_dashboard(): void {
		echo '<div class="wrap"><h1>Studiofy Dashboard</h1><p>Select a module from the menu.</p></div>';
	}

	// Wrapper to call settings class render, ensures context is correct
	public function render_settings(): void {
		// We instantiate the settings class only when needed to keep memory low
		require_once STUDIOFY_PATH . 'admin/class-studiofy-settings.php';
		$settings = new Studiofy_Settings();
		$settings->render_page(); 
	}

	public function execute_invoice_job( array $args ): void {
		// ... existing async logic ...
	}
}
