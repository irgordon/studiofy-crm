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
		// 1. Define the Path
		$admin_file = STUDIOFY_PATH . 'admin/class-studiofy-admin.php';

		// 2. Load the File
		if ( file_exists( $admin_file ) ) {
			require_once $admin_file;
		}

		// 3. CRITICAL CHECK: Does the class exist now?
		// If not, we STOP here to prevent the Fatal Error.
		if ( ! class_exists( 'Studiofy_Admin' ) ) {
			// Log the error to debug.log so you can see it later
			error_log( 'Studiofy Critical Error: Class Studiofy_Admin not found in ' . $admin_file );
			return; // EXIT FUNCTION
		}

		// 4. Load Dependencies
		$files = [
			'admin/class-studiofy-settings.php',
			'admin/class-studiofy-metaboxes.php',
			'includes/class-studiofy-cpt-registrar.php',
			'includes/modules/class-studiofy-contracts.php',
			'includes/modules/class-studiofy-forms.php'
		];

		foreach ( $files as $file ) {
			if ( file_exists( STUDIOFY_PATH . $file ) ) {
				require_once STUDIOFY_PATH . $file;
			}
		}

		// 5. Instantiate (Safe now because we checked class_exists above)
		$plugin_admin = new Studiofy_Admin( $this->plugin_name, $this->version );
		
		// Init Settings
		if ( class_exists( 'Studiofy_Settings' ) ) {
			new Studiofy_Settings();
		}

		// Init Components
		if ( class_exists( 'Studiofy_Contracts' ) ) (new Studiofy_Contracts())->init();
		if ( class_exists( 'Studiofy_CPT_Registrar' ) ) (new Studiofy_CPT_Registrar())->init();
		if ( class_exists( 'Studiofy_Metaboxes' ) ) (new Studiofy_Metaboxes())->init();

		$forms = class_exists( 'Studiofy_Forms_Engine' ) ? new Studiofy_Forms_Engine() : null;

		// 6. Hooks
		add_action( 'admin_menu', array( $plugin_admin, 'add_plugin_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );
		add_action( 'studiofy_async_process_invoice', array( $plugin_admin, 'execute_invoice_job' ) );

		if ( $forms ) {
			add_action( 'admin_post_studiofy_submit_form', array( $forms, 'process_submission' ) );
			add_action( 'admin_post_nopriv_studiofy_submit_form', array( $forms, 'process_submission' ) );
		}
	}

	private function define_public_hooks(): void {
		// Public side logic...
		$pub_file = STUDIOFY_PATH . 'public/class-studiofy-public.php';
		if ( file_exists( $pub_file ) ) require_once $pub_file;
		
		if ( class_exists( 'Studiofy_Public' ) ) {
			$plugin_public = new Studiofy_Public( $this->plugin_name, $this->version );
			add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_scripts' ) );
			add_action( 'init', array( $plugin_public, 'init' ) );
			add_action( 'wp_ajax_studiofy_submit_signature', array( $plugin_public, 'handle_signature_submission' ) );
			add_action( 'wp_ajax_nopriv_studiofy_submit_signature', array( $plugin_public, 'handle_signature_submission' ) );
		}
		
		// Gallery Shortcode
		$gal_file = STUDIOFY_PATH . 'includes/modules/class-studiofy-gallery.php';
		if ( file_exists( $gal_file ) ) require_once $gal_file;
		
		if ( class_exists( 'Studiofy_Gallery' ) ) {
			$gallery = new Studiofy_Gallery();
			add_shortcode( 'studiofy_gallery', array( $gallery, 'render_shortcode' ) );
		}
	}
}
