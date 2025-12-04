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
		// 1. Define Paths
		$admin_class_file = STUDIOFY_PATH . 'admin/class-studiofy-admin.php';
		$settings_file    = STUDIOFY_PATH . 'admin/class-studiofy-settings.php';
		$metaboxes_file   = STUDIOFY_PATH . 'admin/class-studiofy-metaboxes.php';
		$cpt_file         = STUDIOFY_PATH . 'includes/class-studiofy-cpt-registrar.php';

		// 2. Load Dependencies (Check if they exist to prevent WSOD)
		if ( file_exists( $admin_class_file ) ) {
			require_once $admin_class_file;
		} else {
			wp_die( 'Critical Error: Missing admin/class-studiofy-admin.php' );
		}

		require_once $settings_file;
		require_once $metaboxes_file;
		require_once $cpt_file;
		
		// 3. Load Modules
		$contracts_file = STUDIOFY_PATH . 'includes/modules/class-studiofy-contracts.php';
		if ( file_exists( $contracts_file ) ) {
			require_once $contracts_file;
			(new Studiofy_Contracts())->init();
		}
		
		require_once STUDIOFY_PATH . 'includes/modules/class-studiofy-forms.php';
		$forms = new Studiofy_Forms_Engine();

		// 4. Init Components
		(new Studiofy_CPT_Registrar())->init();
		(new Studiofy_Metaboxes())->init();
		
		// 5. Instantiate Admin Class (This is where your error happened)
		if ( class_exists( 'Studiofy_Admin' ) ) {
			$plugin_admin = new Studiofy_Admin( $this->plugin_name, $this->version );
			new Studiofy_Settings();

			add_action( 'admin_menu', array( $plugin_admin, 'add_plugin_admin_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );
			add_action( 'studiofy_async_process_invoice', array( $plugin_admin, 'execute_invoice_job' ) );
		} else {
			// Fail gracefully if class definition is malformed
			add_action( 'admin_notices', function() {
				echo '<div class="error"><p>Studiofy Error: Studiofy_Admin class definition is missing.</p></div>';
			});
		}

		// Handlers
		add_action( 'admin_post_studiofy_submit_form', array( $forms, 'process_submission' ) );
		add_action( 'admin_post_nopriv_studiofy_submit_form', array( $forms, 'process_submission' ) );
	}

	private function define_public_hooks(): void {
		require_once STUDIOFY_PATH . 'public/class-studiofy-public.php';
		require_once STUDIOFY_PATH . 'includes/modules/class-studiofy-gallery.php';

		$plugin_public = new Studiofy_Public( $this->plugin_name, $this->version );
		$gallery       = new Studiofy_Gallery();

		add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_scripts' ) );
		add_action( 'init', array( $plugin_public, 'init' ) );
		add_shortcode( 'studiofy_gallery', array( $gallery, 'render_shortcode' ) );
		
		add_action( 'wp_ajax_studiofy_submit_signature', array( $plugin_public, 'handle_signature_submission' ) );
		add_action( 'wp_ajax_nopriv_studiofy_submit_signature', array( $plugin_public, 'handle_signature_submission' ) );
	}
}
