<?php
declare( strict_types=1 );

/**
 * The admin-specific functionality of the plugin.
 */
class Studiofy_Admin {

	private string $plugin_name;
	private string $version;

	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets and JavaScript for the admin area.
	 */
	public function enqueue_scripts( string $hook ): void {
		// Load Media Uploader for Gallery CPT
		global $post;
		if ( $post && 'studiofy_gallery' === $post->post_type ) {
			wp_enqueue_media();
		}

		// Only load on studiofy pages to preserve admin performance
		if ( false === strpos( $hook, 'studiofy' ) ) {
			return;
		}

		wp_enqueue_style( $this->plugin_name, STUDIOFY_URL . 'admin/css/studiofy-admin.css', array(), $this->version, 'all' );
		wp_enqueue_script( $this->plugin_name . '-admin', STUDIOFY_URL . 'admin/js/studiofy-admin.js', array( 'jquery' ), $this->version, true );
		
		// Load Form Builder on Session CPT
		if ( $post && 'studiofy_session' === $post->post_type ) {
			wp_enqueue_script( $this->plugin_name . '-builder', STUDIOFY_URL . 'admin/js/studiofy-form-builder.js', array( 'jquery', 'jquery-ui-sortable' ), $this->version, true );
		}
	}

	/**
	 * Register the administration menu.
	 */
	public function add_plugin_admin_menu(): void {
		add_menu_page(
			'Studiofy', 
			'Studiofy', 
			'manage_studiofy_crm', 
			'studiofy-dashboard', 
			array( $this, 'render_dashboard' ), 
			'dashicons-camera', 
			6 
		);
	}

	/**
	 * Render the main dashboard (Welcome Screen)
	 */
	public function render_dashboard(): void {
		?>
		<div class="wrap">
			<h1>Studiofy Dashboard</h1>
			<div class="card" style="max-width: 600px;">
				<h2>Welcome to Studiofy CRM v5.0</h2>
				<p>Your studio management system is active.</p>
				<hr>
				<p>
					<a href="edit.php?post_type=studiofy_project" class="button button-primary">Manage Projects</a>
					<a href="edit.php?post_type=studiofy_invoice" class="button">Invoices</a>
					<a href="admin.php?page=studiofy-settings" class="button">Settings</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Async job handler for invoice generation
	 */
	public function execute_invoice_job( array $args ): void {
		require_once STUDIOFY_PATH . 'includes/integrations/class-studiofy-square-api.php';
		// Logic to call Square API based on $args would go here.
		// This keeps the UI snappy by running API calls in the background.
	}
}
