<?php
declare( strict_types=1 );

class Studiofy_Settings {

	public function __construct() {
		add_action( 'admin_init', array( $this, 'init' ) );
		// Removed 'admin_menu' hook here to prevent double registration.
		// The menu is now handled solely by class-studiofy-admin.php
	}

	public function init(): void {
		register_setting( 'studiofy_options', 'studiofy_settings', array( $this, 'sanitize' ) );
		
		add_settings_section( 'api_keys', 'API Integrations', null, 'studiofy-settings' );
		
		add_settings_field( 'square_access_token', 'Square Token', array( $this, 'field_token' ), 'studiofy-settings', 'api_keys' );
		add_settings_field( 'square_location_id', 'Location ID', array( $this, 'field_loc' ), 'studiofy-settings', 'api_keys' );
		add_settings_field( 'google_client_id', 'Google Client ID', array( $this, 'field_gcal' ), 'studiofy-settings', 'api_keys' );
	}

	public function sanitize( array $input ): array {
		$new = array();
		$enc = new Studiofy_Encryption();
		$old = get_option( 'studiofy_settings', array() );
		
		// Encrypt token if changed
		if ( ! empty( $input['square_access_token'] ) ) {
			$new['square_access_token'] = $enc->encrypt( $input['square_access_token'] );
		} else {
			$new['square_access_token'] = $old['square_access_token'] ?? '';
		}

		$new['square_location_id'] = sanitize_text_field( $input['square_location_id'] ?? '' );
		$new['google_client_id']   = sanitize_text_field( $input['google_client_id'] ?? '' );
		
		return $new;
	}

	public function field_token(): void {
		echo '<input type="password" name="studiofy_settings[square_access_token]" class="regular-text" placeholder="Encrypted">';
	}
	public function field_loc(): void {
		$o = get_option( 'studiofy_settings', array() );
		echo '<input type="text" name="studiofy_settings[square_location_id]" value="' . esc_attr( $o['square_location_id'] ?? '' ) . '" class="regular-text">';
	}
	public function field_gcal(): void {
		$o = get_option( 'studiofy_settings', array() );
		echo '<input type="text" name="studiofy_settings[google_client_id]" value="' . esc_attr( $o['google_client_id'] ?? '' ) . '" class="regular-text">';
	}

	public function render_page(): void {
		// --- HANDLE DEMO ACTIONS ---
		// FIX: Only check nonce IF the form was submitted
		if ( isset( $_POST['studiofy_action_flag'] ) && check_admin_referer( 'studiofy_seed_nonce' ) ) {
			
			require_once STUDIOFY_PATH . 'includes/utils/class-studiofy-seeder.php';
			$seeder = new Studiofy_Seeder();

			if ( isset( $_POST['studiofy_install_demo'] ) ) {
				$seeder->run_seed();
				echo '<div class="notice notice-success is-dismissible"><p>Demo Data Installed.</p></div>';
			}
			
			if ( isset( $_POST['studiofy_remove_demo'] ) ) {
				$seeder->purge_demo_data();
				echo '<div class="notice notice-warning is-dismissible"><p>Demo Data Removed.</p></div>';
			}
		}

		?>
		<div class="wrap">
			<h1>Studiofy Settings</h1>
			
			<div class="card" style="max-width:800px; margin-top:20px; border-left:4px solid #72aee6; padding:15px;">
				<h3>🛠️ Data Management</h3>
				<form method="post" style="display:flex; gap:10px;">
					<?php wp_nonce_field( 'studiofy_seed_nonce' ); ?>
					<input type="hidden" name="studiofy_action_flag" value="1">
					
					<button type="submit" name="studiofy_install_demo" value="1" class="button button-secondary">Install Demo Data</button>
					<button type="submit" name="studiofy_remove_demo" value="1" class="button button-link-delete" onclick="return confirm('Are you sure? This deletes all demo content.');">Remove Demo Data</button>
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
