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
		// ... existing project/lead boxes ...
		add_meta_box( 'studiofy_contract_terms', 'Payment & Terms', array( $this, 'render_contract_terms' ), 'studiofy_contract', 'normal', 'high' );
		add_meta_box( 'studiofy_contract_clauses', 'Contract Clauses (Terms & Conditions)', array( $this, 'render_contract_clauses' ), 'studiofy_contract', 'normal', 'high' );
		add_meta_box( 'studiofy_contract_status', 'Status', array( $this, 'render_contract_status' ), 'studiofy_contract', 'side' );
	}

	// 1. PAYMENT & TERMS CONFIG
	public function render_contract_terms( WP_Post $post ): void {
		wp_nonce_field( 'studiofy_meta', 'studiofy_nonce' );
		$type = get_post_meta( $post->ID, '_studiofy_payment_type', true );
		$fee  = get_post_meta( $post->ID, '_studiofy_contract_fee', true );
		$dep  = get_post_meta( $post->ID, '_studiofy_deposit_pct', true );
		
		echo '<div class="studiofy-meta-grid">';
		echo '<div><label>Payment Structure:</label> <select name="studiofy_payment_type" class="widefat">';
		foreach(['Per Job (Flat Fee)', 'Per Hour', 'Per Day', 'Retainer'] as $t) echo '<option '.selected($type, $t, false).'>'.$t.'</option>';
		echo '</select></div>';
		
		echo '<div><label>Total Fee ($):</label> <input type="number" step="0.01" name="studiofy_contract_fee" value="'.esc_attr($fee).'" class="widefat"></div>';
		echo '<div><label>Deposit Required (%):</label> <input type="number" name="studiofy_deposit_pct" value="'.esc_attr($dep).'" class="widefat" placeholder="e.g. 50"></div>';
		echo '</div>';
		echo '<p class="description">This data populates the "Fees" section of the contract.</p>';
	}

	// 2. DYNAMIC CLAUSE BUILDER (Repeater)
	public function render_contract_clauses( WP_Post $post ): void {
		$json = get_post_meta( $post->ID, '_studiofy_clauses', true );
		$clauses = !empty($json) ? json_decode($json, true) : [];
		?>
		<div id="studiofy-clause-container">
			<?php if(is_array($clauses)): foreach($clauses as $i => $c): ?>
				<div class="clause-row" style="background:#f9f9f9; border:1px solid #ddd; padding:15px; margin-bottom:15px; border-radius:4px;">
					<div style="display:flex; justify-content:space-between; margin-bottom:10px;">
						<input type="text" class="clause-title widefat" value="<?php echo esc_attr($c['title']); ?>" style="font-weight:bold; width:80%;" placeholder="Clause Title (e.g. Copyright)">
						<span class="dashicons dashicons-trash remove-clause" style="cursor:pointer; color:#d63638;"></span>
					</div>
					<textarea class="clause-body widefat" rows="5" placeholder="Enter legal text here..."><?php echo esc_textarea($c['body']); ?></textarea>
				</div>
			<?php endforeach; endif; ?>
		</div>
		<button type="button" class="button button-primary" id="add-clause">Add Clause</button>
		<input type="hidden" name="studiofy_clauses" id="studiofy_clauses_input" value="<?php echo esc_attr($json); ?>">
		<?php
	}

	// 3. STATUS SIDEBAR
	public function render_contract_status( WP_Post $post ): void {
		$status = get_post_meta( $post->ID, '_studiofy_status', true );
		$client = get_post_meta( $post->ID, '_studiofy_client_id', true );
		
		// Link Client
		global $wpdb;
		$clients = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}studiofy_clients" );
		
		echo '<p><label>Client:</label> <select name="studiofy_client_id" class="widefat"><option value="">Select...</option>';
		foreach($clients as $c) echo '<option value="'.$c->id.'" '.selected($client, $c->id, false).'>'.esc_html($c->name).'</option>';
		echo '</select></p>';

		echo '<p><label>Status:</label> <select name="studiofy_status" class="widefat">';
		foreach(['Draft','Pending','Signed','Cancelled'] as $s) echo '<option '.selected($status, $s, false).'>'.$s.'</option>';
		echo '</select></p>';
		
		echo '<p><a href="'.esc_url(add_query_arg('print_contract',$post->ID,home_url())).'" target="_blank" class="button">View / Print</a></p>';
	}

	public function save( int $post_id ): void {
		if ( ! isset( $_POST['studiofy_nonce'] ) || ! wp_verify_nonce( $_POST['studiofy_nonce'], 'studiofy_meta' ) ) return;
		
		$keys = [
			'studiofy_payment_type', 'studiofy_contract_fee', 'studiofy_deposit_pct', 
			'studiofy_clauses', 'studiofy_status', 'studiofy_client_id'
		];
		
		foreach ( $keys as $k ) {
			if ( isset( $_POST[ $k ] ) ) {
				// Allow HTML in clauses (for bolding/lists in contracts)
				if($k === 'studiofy_clauses') update_post_meta( $post_id, '_' . $k, wp_kses_post( $_POST[ $k ] ) );
				else update_post_meta( $post_id, '_' . $k, sanitize_text_field( $_POST[ $k ] ) );
			}
		}
	}
}
