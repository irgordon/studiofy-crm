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
		
		// 1. Media Uploader (Gallery)
		if ( $post && 'studiofy_gallery' === $post->post_type ) {
			wp_enqueue_media();
		}

		// 2. Only load CSS/JS on Studiofy Pages
		if ( false === strpos( $hook, 'studiofy' ) && false === strpos( $hook, 'post.php' ) && false === strpos( $hook, 'post-new.php' ) ) {
			return;
		}

		if ( file_exists( STUDIOFY_PATH . 'admin/css/studiofy-admin.css' ) ) {
			wp_enqueue_style( $this->plugin_name, STUDIOFY_URL . 'admin/css/studiofy-admin.css', array(), $this->version );
		}
		
		if ( file_exists( STUDIOFY_PATH . 'admin/js/studiofy-admin.js' ) ) {
			wp_enqueue_script( $this->plugin_name . '-admin', STUDIOFY_URL . 'admin/js/studiofy-admin.js', array( 'jquery' ), $this->version, true );
		}
		
		if ( $post && 'studiofy_session' === $post->post_type ) {
			if ( file_exists( STUDIOFY_PATH . 'admin/js/studiofy-form-builder.js' ) ) {
				wp_enqueue_script( $this->plugin_name . '-builder', STUDIOFY_URL . 'admin/js/studiofy-form-builder.js', array( 'jquery', 'jquery-ui-sortable' ), $this->version, true );
			}
		}
		
		if ( $post && 'studiofy_contract' === $post->post_type ) {
			if ( file_exists( STUDIOFY_PATH . 'admin/js/studiofy-contract-builder.js' ) ) {
				wp_enqueue_script( $this->plugin_name . '-contract', STUDIOFY_URL . 'admin/js/studiofy-contract-builder.js', array( 'jquery', 'jquery-ui-sortable' ), $this->version, true );
			}
		}
	}

	public function add_plugin_admin_menu(): void {
		add_menu_page( 
			'Studiofy', 
			'Studiofy', 
			'manage_options', 
			'studiofy-dashboard', 
			array( $this, 'render_dashboard' ), 
			'dashicons-camera', 
			6 
		);

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
		echo '<div class="wrap"><h1>Studiofy Dashboard</h1><div class="card" style="max-width:600px; padding:20px; margin-top:20px;"><h2>Welcome</h2><p>Select a module to begin.</p><hr><p><a href="edit.php?post_type=studiofy_project" class="button button-primary">Manage Projects</a> <a href="edit.php?post_type=studiofy_invoice" class="button">Invoices</a></p></div></div>';
	}

	public function render_settings(): void {
		if ( class_exists( 'Studiofy_Settings' ) ) {
			(new Studiofy_Settings())->render_page();
		} else {
			echo '<div class="error"><p>Settings Class not loaded.</p></div>';
		}
	}

	public function execute_invoice_job( array $args ): void {
		// Async logic
	}
}
