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
		if ( false === strpos( $hook, 'studiofy' ) ) return;
		wp_enqueue_style( $this->plugin_name, STUDIOFY_URL . 'admin/css/studiofy-admin.css', array(), $this->version );
		wp_enqueue_script( $this->plugin_name, STUDIOFY_URL . 'admin/js/studiofy-admin.js', array( 'jquery' ), $this->version, true );
	}

	public function add_plugin_admin_menu(): void {
		add_menu_page( 'Studiofy', 'Studiofy', 'manage_studiofy_crm', 'studiofy-dashboard', array( $this, 'render_dashboard' ), 'dashicons-camera', 6 );
	}

	public function render_dashboard(): void {
		echo '<div class="wrap"><h1>Studiofy Dashboard</h1><p>Welcome to v3.0 Native CRM.</p></div>';
	}

	public function execute_invoice_job( array $args ): void {
		require_once STUDIOFY_PATH . 'includes/integrations/class-studiofy-square-api.php';
		$api = new Studiofy_Square_API();
		// Mock payload execution
		// $api->create_invoice(...);
	}
}
