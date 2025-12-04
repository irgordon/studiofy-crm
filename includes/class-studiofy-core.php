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
		// 1. FORCE LOAD THE ADMIN CLASS FILE
		// We explicitly require this first to prevent "Class not found" errors
		if ( file_exists( STUDIOFY_PATH . 'admin/class-studiofy-admin.php' ) ) {
			require_once STUDIOFY_PATH . 'admin/class-studiofy-admin.php';
		} else {
			// Stop execution gracefully if file is missing to prevent WSOD
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-error"><p>Critical Error: admin/class-studiofy-admin.php is missing.</p></div>';
			});
			return; 
		}

		// 2. Load Other Dependencies
		$files = [
			'admin/class-studiofy-settings.php',
			'admin/class-studiofy-metaboxes.php',
			'includes/class-studiofy-cpt-registrar.php',
			'includes/modules/class-studiofy-contracts.php',
			'includes/modules/class-studiofy-forms.php',
			// 'includes/modules/class-studiofy-kanban.php' // Uncomment if you are using the Kanban module
		];

		foreach ( $files as $file ) {
			if ( file_exists( STUDIOFY_PATH . $file ) ) {
				require_once STUDIOFY_PATH . $file;
			}
		}

		// 3. Initialize Components
		if ( class_exists( 'Studiofy_Contracts' ) ) (new Studiofy_Contracts())->init();
		if ( class_exists( 'Studiofy_CPT_Registrar' ) ) (new Studiofy_CPT_Registrar())->init();
		if ( class_exists( 'Studiofy_Metaboxes' ) ) (new Studiofy_Metaboxes())->init();
		
		$forms = class_exists( 'Studiofy_Forms_Engine' ) ? new Studiofy_Forms_Engine() : null;

		// 4. Instantiate Admin Class (SAFE CHECK)
		// This block wraps the instantiation to ensure the class actually exists in memory now
		if ( class_exists( 'Studiofy_Admin' ) ) {
			$plugin_admin = new Studiofy_Admin( $this->plugin_name, $this->version );
			
			// Init Settings if available
			if ( class_exists( 'Studiofy_Settings' ) ) {
				new Studiofy_Settings();
			}

			add_action( 'admin_menu', array( $plugin_admin, 'add_plugin_admin_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );
			add_action( 'studiofy_async_process_invoice', array( $plugin_admin, 'execute_invoice_job' ) );
		}

		// 5. Form Handlers
		if ( $forms ) {
			add_action( 'admin_post_studiofy_submit_form', array( $forms, 'process_submission' ) );
			add_action( 'admin_post_nopriv_studiofy_submit_form', array( $forms, 'process_submission' ) );
		}
	}

	private function define_public_hooks(): void {
		// Load Public Dependencies
		if ( file_exists( STUDIOFY_PATH . 'public/class-studiofy-public.php' ) ) {
			require_once STUDIOFY_PATH . 'public/class-studiofy-public.php';
		}
		if ( file_exists( STUDIOFY_PATH . 'includes/modules/class-studiofy-gallery.php' ) ) {
			require_once STUDIOFY_PATH . 'includes/modules/class-studiofy-gallery.php';
		}

		if ( class_exists( 'Studiofy_Public' ) ) {
			$plugin_public = new Studiofy_Public( $this->plugin_name, $this->version );
			add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_scripts' ) );
			add_action( 'init', array( $plugin_public, 'init' ) );
			add_action( 'wp_ajax_studiofy_submit_signature', array( $plugin_public, 'handle_signature_submission' ) );
			add_action( 'wp_ajax_nopriv_studiofy_submit_signature', array( $plugin_public, 'handle_signature_submission' ) );
		}

		if ( class_exists( 'Studiofy_Gallery' ) ) {
			$gallery = new Studiofy_Gallery();
			add_shortcode( 'studiofy_gallery', array( $gallery, 'render_shortcode' ) );
		}
	}
}
