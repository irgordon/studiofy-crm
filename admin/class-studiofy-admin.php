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
		// Load Media Uploader for Gallery CPT
		global $post;
		if ( $post && 'studiofy_gallery' === $post->post_type ) {
			wp_enqueue_media();
		}

		// Only load CSS/JS on Studiofy pages
		if ( false === strpos( $hook, 'studiofy' ) ) {
			return;
		}

		wp_enqueue_style( $this->plugin_name, STUDIOFY_URL . 'admin/css/studiofy-admin.css', array(), $this->version );
		wp_enqueue_script( $this->plugin_name . '-admin', STUDIOFY_URL . 'admin/js/studiofy-admin.js', array( 'jquery' ), $this->version, true );
		
		// Load Form Builder on Session CPT
		if ( $post && 'studiofy_session' === $post->post_type ) {
			wp_enqueue_script( $this->plugin_name . '-builder', STUDIOFY_URL . 'admin/js/studiofy-form-builder.js', array( 'jquery', 'jquery-ui-sortable' ), $this->version, true );
		}
	}

	public function add_plugin_admin_menu(): void {
		// FIX: changed 'manage_studiofy_crm' to 'manage_options'
		add_menu_page( 
			'Studiofy', 
			'Studiofy', 
			'manage_options', 
			'studiofy-dashboard', 
			array( $this, 'render_dashboard' ), 
			'dashicons-camera', 
			6 
		);
	}

	public function render_dashboard(): void {
		?>
		<div class="wrap">
			<h1>Studiofy Dashboard</h1>
			<div class="card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
				<h2>Welcome Back</h2>
				<p>Select a module to begin:</p>
				<hr>
				<p>
					<a href="edit.php?post_type=studiofy_project" class="button button-primary">Manage Projects</a>
					<a href="edit.php?post_type=studiofy_lead" class="button">Leads</a>
					<a href="edit.php?post_type=studiofy_invoice" class="button">Invoices</a>
					<a href="admin.php?page=studiofy-settings" class="button">Settings</a>
				</p>
			</div>
		</div>
		<?php
	}

	public function execute_invoice_job( array $args ): void {
		require_once STUDIOFY_PATH . 'includes/integrations/class-studiofy-square-api.php';
		// Logic to call Square API...
	}
}
