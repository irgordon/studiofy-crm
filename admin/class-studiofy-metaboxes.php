<?php
declare( strict_types=1 );

class Studiofy_Metaboxes {

	public function init(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_boxes' ) );
		add_action( 'save_post', array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets( string $hook ): void {
		global $post;
		if ( $post && 'studiofy_contract' === $post->post_type ) {
			wp_enqueue_script( 'studiofy-contract-builder', STUDIOFY_URL . 'admin/js/studiofy-contract-builder.js', array( 'jquery', 'jquery-ui-sortable' ), STUDIOFY_VERSION, true );
		}
	}

	public function add_boxes(): void {
		// Project
		add_meta_box( 'studiofy_proj_cfg', 'Project Config', array( $this, 'render_project' ), 'studiofy_project', 'normal', 'high' );
		
		// Lead
		add_meta_box( 'studiofy_lead_info', 'Lead Details', array( $this, 'render_lead' ), 'studiofy_lead', 'normal', 'high' );
		
		// Session
		add_meta_box( 'studiofy_sess_form', 'Form Builder', array( $this, 'render_form' ), 'studiofy_session', 'normal' );
		
		// Gallery
		add_meta_box( 'studiofy_gal_img', 'Gallery Images', array( $this, 'render_gallery' ), 'studiofy_gallery', 'normal' );
		
		// Contract
		add_meta_box( 'studiofy_cont_term', 'Payment & Terms', array( $this, 'render_contract_terms' ), 'studiofy_contract', 'normal' );
		add_meta_box( 'studiofy_cont_clause', 'Clauses', array( $this, 'render_contract_clauses' ), 'studiofy_contract', 'normal' );
		add_meta_box( 'studiofy_cont_stat', 'Status', array( $this, 'render_contract_status' ), 'studiofy_contract', 'side' );
	}

	// 1. PROJECT
	public function render_project( WP_Post $post ): void {
		wp_nonce_field( 'studiofy_meta', 'studiofy_nonce' );
		$client = get_post_meta( $post->ID, '_studiofy_client_id', true );
		$type   = get_post_meta( $post->ID, '_studiofy_project_type', true );
		$status = get_post_meta( $post->ID, '_studiofy_status', true );
		$phase  = get_post_meta( $post->ID, '_studiofy_phase', true );
		$inv    = get_post_meta( $post->ID, '_studiofy_square_invoice_id', true );

		global $wpdb;
		$clients = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}studiofy_clients" );
		?>
		<div class="studiofy-meta-grid">
			<div>
				<label>Client:</label> 
				<select name="studiofy_client_id" class="widefat">
					<option value="">Select...</option>
					<?php foreach($clients as $c) echo '<option value="'.$c->id.'" '.selected($client, $c->id, false).'>'.esc_html($c->name).'</option>'; ?>
				</select>
			</div>
			<div><label>Type:</label> <select name="studiofy_project_type" class="widefat"><?php foreach(['Portrait','Wedding','Event'] as $t) echo '<option '.selected($type, $t, false).'>'.$t.'</option>'; ?></select></div>
			<div><label>Status:</label> <select name="studiofy_status" class="widefat"><?php foreach(['New','In Progress','Complete'] as $s) echo '<option '.selected($status, $s, false).'>'.$s.'</option>'; ?></select></div>
			<div><label>Phase:</label> <select name="studiofy_phase" class="widefat"><?php foreach(['Pre-Production','Production','Post-Production'] as $p) echo '<option '.selected($phase, $p, false).'>'.$p.'</option>'; ?></select></div>
			<div><label>Invoice ID:</label> <input type="text" name="studiofy_square_invoice_id" value="<?php echo esc_attr($inv); ?>" class="widefat"></div>
		</div>
		<?php
	}

	// 2. LEAD (With Custom Fields)
	public function render_lead( WP_Post $post ): void {
		$email = get_post_meta( $post->ID, '_studiofy_email', true );
		$phone = get_post_meta( $post->ID, '_studiofy_phone', true );
		$c1    = get_post_meta( $post->ID, '_studiofy_custom_1', true );
		$c2    = get_post_meta( $post->ID, '_studiofy_custom_2', true );
		$notes = get_post_meta( $post->ID, '_studiofy_notes', true );

		echo '<div class="studiofy-meta-grid">';
		echo '<div><label>Email:</label> <input type="email" name="studiofy_email" value="'.esc_attr($email).'" class="widefat"></div>';
		echo '<div><label>Phone:</label> <input type="text" name="studiofy_phone" value="'.esc_attr($phone).'" class="widefat"></div>';
		echo '<div><label>Custom 1:</label> <input type="text" name="studiofy_custom_1" value="'.esc_attr($c1).'" class="widefat"></div>';
		echo '<div><label>Custom 2:</label> <input type="text" name="studiofy_custom_2" value="'.esc_attr($c2).'" class="widefat"></div>';
		echo '</div>';
		echo '<p><label>Notes:</label></p>';
		wp_editor( $notes, 'studiofy_notes', array( 'textarea_name' => 'studiofy_notes', 'media_buttons' => false, 'textarea_rows' => 5 ) );
	}

	// 3. FORM BUILDER
	public function render_form( WP_Post $post ): void {
		$json = get_post_meta( $post->ID, '_studiofy_form_schema', true );
		echo '<div id="studiofy-fields-container"></div><button type="button" class="button" id="add-field">Add Field</button>';
		echo '<input type="hidden" name="studiofy_form_schema" id="studiofy_form_schema_input" value="'.esc_attr($json).'">';
	}

	// 4. GALLERY
	public function render_gallery( WP_Post $post ): void {
		$ids = get_post_meta( $post->ID, '_studiofy_gallery_ids', true );
		echo '<input type="hidden" name="studiofy_gallery_ids" id="studiofy_gallery_ids" value="'.esc_attr($ids).'"><button id="studiofy-select-images" class="button">Select Images</button>';
	}

	// 5. CONTRACT - TERMS
	public function render_contract_terms( WP_Post $post ): void {
		$type = get_post_meta( $post->ID, '_studiofy_payment_type', true );
		$fee  = get_post_meta( $post->ID, '_studiofy_contract_fee', true );
		echo '<p><label>Payment:</label> <select name="studiofy_payment_type" class="widefat">';
		foreach(['Per Job','Hourly','Retainer'] as $t) echo '<option '.selected($type, $t, false).'>'.$t.'</option>';
		echo '</select></p>';
		echo '<p><label>Fee ($):</label> <input type="number" step="0.01" name="studiofy_contract_fee" value="'.esc_attr($fee).'" class="widefat"></p>';
	}

	// 6. CONTRACT - CLAUSES
	public function render_contract_clauses( WP_Post $post ): void {
		$json = get_post_meta( $post->ID, '_studiofy_clauses', true );
		echo '<div id="studiofy-clause-container"></div><button type="button" class="button" id="add-clause">Add Clause</button>';
		echo '<input type="hidden" name="studiofy_clauses" id="studiofy_clauses_input" value="'.esc_attr($json).'">';
	}

	// 7. CONTRACT - STATUS
	public function render_contract_status( WP_Post $post ): void {
		$status = get_post_meta( $post->ID, '_studiofy_status', true );
		$client = get_post_meta( $post->ID, '_studiofy_client_id', true );
		global $wpdb;
		$clients = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}studiofy_clients" );
		
		echo '<p><label>Client:</label> <select name="studiofy_client_id" class="widefat"><option value="">Select...</option>';
		foreach($clients as $c) echo '<option value="'.$c->id.'" '.selected($client, $c->id, false).'>'.esc_html($c->name).'</option>';
		echo '</select></p>';
		
		echo '<select name="studiofy_status" class="widefat">';
		foreach(['Draft','Pending','Signed'] as $s) echo '<option '.selected($status, $s, false).'>'.$s.'</option>';
		echo '</select><br><br>';
		echo '<a href="'.esc_url(add_query_arg('print_contract',$post->ID,home_url())).'" target="_blank" class="button">Print View</a>';
	}

	// SAVE LOGIC
	public function save( int $id ): void {
		if ( ! isset( $_POST['studiofy_nonce'] ) || ! wp_verify_nonce( $_POST['studiofy_nonce'], 'studiofy_meta' ) ) return;
		
		$keys = [
			'studiofy_client_id', 'studiofy_project_type', 'studiofy_status', 'studiofy_phase', 
			'studiofy_square_invoice_id', 'studiofy_email', 'studiofy_phone', 'studiofy_custom_1', 
			'studiofy_custom_2', 'studiofy_form_schema', 'studiofy_gallery_ids', 
			'studiofy_payment_type', 'studiofy_contract_fee', 'studiofy_clauses'
		];

		foreach ( $keys as $k ) {
			if ( isset( $_POST[ $k ] ) ) {
				// Handle raw HTML/JSON vs Text
				if ( in_array( $k, ['studiofy_clauses', 'studiofy_form_schema'] ) ) {
					update_post_meta( $id, '_' . $k, wp_kses_post( $_POST[ $k ] ) );
				} else {
					update_post_meta( $id, '_' . $k, sanitize_text_field( $_POST[ $k ] ) );
				}
			}
		}
		// Handle Editor
		if ( isset( $_POST['studiofy_notes'] ) ) update_post_meta( $id, '_studiofy_notes', wp_kses_post( $_POST['studiofy_notes'] ) );
	}
}
