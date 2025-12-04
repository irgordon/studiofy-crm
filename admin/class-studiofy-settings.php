<?php
class Studiofy_Settings {
	// ... construct/init/sanitize/fields ...

	public function render_page() {
		// --- DEMO ACTIONS ---
		if ( check_admin_referer( 'studiofy_seed_nonce' ) ) {
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
				<h3>🛠️ Data Tools</h3>
				<form method="post" style="display:flex; gap:10px;">
					<?php wp_nonce_field( 'studiofy_seed_nonce' ); ?>
					<button type="submit" name="studiofy_install_demo" value="1" class="button button-secondary">Install Demo Data</button>
					<button type="submit" name="studiofy_remove_demo" value="1" class="button button-link-delete" onclick="return confirm('Remove all demo data?');">Remove Demo Data</button>
				</form>
			</div>
			</div>
		<?php
	}
}
