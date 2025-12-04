<?php
class Studiofy_Settings {
	public function __construct() {
		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'menu' ) );
	}

	public function menu() {
		add_submenu_page( 'studiofy-dashboard', 'Settings', 'Settings', 'manage_options', 'studiofy-settings', array( $this, 'render_page' ) );
	}

	public function init() {
		register_setting( 'studiofy_options', 'studiofy_settings', array( $this, 'sanitize' ) );
		add_settings_section( 'api_keys', 'API Integrations', null, 'studiofy-settings' );
		add_settings_field( 'square_access_token', 'Square Token', array( $this, 'field_token' ), 'studiofy-settings', 'api_keys' );
		add_settings_field( 'square_location_id', 'Location ID', array( $this, 'field_loc' ), 'studiofy-settings', 'api_keys' );
		add_settings_field( 'google_client_id', 'Google Client ID', array( $this, 'field_gcal' ), 'studiofy-settings', 'api_keys' );
	}

	public function sanitize( $input ) {
		$new = array();
		$enc = new Studiofy_Encryption();
		
		if ( ! empty( $input['square_access_token'] ) ) {
			$new['square_access_token'] = $enc->encrypt( $input['square_access_token'] );
		} else {
			$old = get_option( 'studiofy_settings' );
			$new['square_access_token'] = $old['square_access_token'] ?? '';
		}

		$new['square_location_id'] = sanitize_text_field( $input['square_location_id'] );
		$new['google_client_id']   = sanitize_text_field( $input['google_client_id'] );
		
		return $new;
	}

	public function field_token() {
		echo '<input type="password" name="studiofy_settings[square_access_token]" class="regular-text" placeholder="Encrypted">';
	}
	public function field_loc() {
		$o = get_option( 'studiofy_settings' );
		echo '<input type="text" name="studiofy_settings[square_location_id]" value="' . esc_attr( $o['square_location_id'] ?? '' ) . '" class="regular-text">';
	}
	public function field_gcal() {
		$o = get_option( 'studiofy_settings' );
		echo '<input type="text" name="studiofy_settings[google_client_id]" value="' . esc_attr( $o['google_client_id'] ?? '' ) . '" class="regular-text">';
	}

	public function render_page() {
		// --- HANDLE DEMO DATA TRIGGER ---
		if ( isset( $_POST['studiofy_seed_demo'] ) && check_admin_referer( 'studiofy_seed_nonce' ) ) {
			// FIX: Explicitly require the file before using the class
			$seeder_file = STUDIOFY_PATH . 'includes/utils/class-studiofy-seeder.php';
			
			if ( file_exists( $seeder_file ) ) {
				require_once $seeder_file;
				
				if ( class_exists( 'Studiofy_Seeder' ) ) {
					(new Studiofy_Seeder())->run_seed();
					echo '<div class="notice notice-success is-dismissible"><p>Demo Data Installed Successfully.</p></div>';
				} else {
					echo '<div class="notice notice-error"><p>Error: Studiofy_Seeder class not defined in file.</p></div>';
				}
			} else {
				echo '<div class="notice notice-error"><p>Error: Seeder file not found at ' . esc_html( $seeder_file ) . '</p></div>';
			}
		}

		// --- HANDLE IMPORTER ---
		if ( isset( $_POST['studiofy_import_source'] ) && check_admin_referer( 'studiofy_import' ) ) {
			// Ensure Importer is loaded (if you added this file previously)
			// require_once STUDIOFY_PATH . 'admin/class-studiofy-importer.php';
			// (new Studiofy_Importer())->run_import( ... );
		}

		?>
		<div class="wrap">
			<h1>Studiofy Settings</h1>
			
			<div class="card" style="max-width:800px; margin-top:20px; border-left:4px solid #72aee6; padding:15px;">
				<h3>🛠️ Developer Tools</h3>
				<p>Populate the CRM with sample clients, contracts, and projects.</p>
				<form method="post">
					<?php wp_nonce_field( 'studiofy_seed_nonce' ); ?>
					<input type="hidden" name="studiofy_seed_demo" value="1">
					<button type="submit" class="button button-secondary">Install Demo Data</button>
				</form>
			</div>

			<hr>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'studiofy_options' );
				do_settings_sections( 'studiofy-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
