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
		// Enqueue logic...
		if ( false === strpos( $hook, 'studiofy' ) && false === strpos( $hook, 'post.php' ) ) {
			return;
		}
		// Assuming CSS/JS files exist
		wp_enqueue_style( $this->plugin_name, STUDIOFY_URL . 'admin/css/studiofy-admin.css', array(), $this->version );
	}

	public function add_plugin_admin_menu(): void {
		add_menu_page( 'Studiofy', 'Studiofy', 'manage_options', 'studiofy-dashboard', array( $this, 'render_dashboard' ), 'dashicons-camera', 6 );
		add_submenu_page( 'studiofy-dashboard', 'Settings', 'Settings', 'manage_options', 'studiofy-settings', array( $this, 'render_settings' ) );
	}

	public function render_dashboard(): void {
		echo '<div class="wrap"><h1>Studiofy Dashboard</h1></div>';
	}

	public function render_settings(): void {
		if ( class_exists( 'Studiofy_Settings' ) ) {
			(new Studiofy_Settings())->render_page();
		}
	}

	public function execute_invoice_job( array $args ): void {
		// Async job logic
	}
}
