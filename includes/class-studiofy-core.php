<?php
declare( strict_types=1 );

class Studiofy_Core {
	protected string $plugin_name = 'studiofy-crm';
	protected string $version     = STUDIOFY_VERSION;

	public function run(): void {
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	private function define_admin_hooks(): void {
		require_once STUDIOFY_PATH . 'admin/class-studiofy-admin.php';
		require_once STUDIOFY_PATH . 'admin/class-studiofy-settings.php';
		require_once STUDIOFY_PATH . 'includes/class-studiofy-cpt-registrar.php';
		require_once STUDIOFY_PATH . 'admin/class-studiofy-metaboxes.php';

		// Init CPTs
		$cpts = new Studiofy_CPT_Registrar();
		$cpts->init();

		// Init Metaboxes
		$metaboxes = new Studiofy_Metaboxes();
		$metaboxes->init();

		$plugin_admin = new Studiofy_Admin( $this->plugin_name, $this->version );
		new Studiofy_Settings();

		add_action( 'admin_menu', array( $plugin_admin, 'add_plugin_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );
		
		// Async Handlers
		add_action( 'studiofy_async_process_invoice', array( $plugin_admin, 'execute_invoice_job' ) );
	}

	private function define_public_hooks(): void {
		require_once STUDIOFY_PATH . 'public/class-studiofy-public.php';
		$plugin_public = new Studiofy_Public( $this->plugin_name, $this->version );

		add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_scripts' ) );
		add_action( 'init', array( $plugin_public, 'init' ) );
		
		// AJAX
		add_action( 'wp_ajax_studiofy_submit_signature', array( $plugin_public, 'handle_signature_submission' ) );
		add_action( 'wp_ajax_nopriv_studiofy_submit_signature', array( $plugin_public, 'handle_signature_submission' ) );
	}
}
