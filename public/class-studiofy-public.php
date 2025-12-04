<?php
declare( strict_types=1 );

class Studiofy_Public {

	private string $version;
	private string $plugin_name;

	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Enqueue Frontend Scripts
	 */
	public function enqueue_scripts(): void {
		global $post;
		
		// Only load on Contract CPT
		if ( is_a( $post, 'WP_Post' ) && 'studiofy_contract' === $post->post_type ) {
			// Signature Pad Library (CDN)
			wp_enqueue_script( 'sig-pad', 'https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js', array(), '4.0', true );
			
			// Custom Public JS
			wp_enqueue_script( 'studiofy-public', STUDIOFY_URL . 'public/js/studiofy-public.js', array( 'jquery', 'sig-pad' ), $this->version, true );
			
			// Pass Data to JS
			wp_localize_script( 
				'studiofy-public', 
				'studiofy_vars', 
				array( 
					'ajax_url' => admin_url( 'admin-ajax.php' ), 
					'nonce'    => wp_create_nonce( 'studiofy_sign' ), 
					'post_id'  => $post->ID 
				) 
			);
		}
	}

	/**
	 * Initialize Hooks
	 */
	public function init(): void {
		add_filter( 'the_content', array( $this, 'append_signature_pad' ) );
	}

	/**
	 * Render Signature Pad on Contract Page
	 */
	public function append_signature_pad( string $content ): string {
		if ( is_singular( 'studiofy_contract' ) ) {
			$sig = get_post_meta( get_the_ID(), '_studiofy_signature', true );
			
			// Show Signature Image if Signed
			if ( $sig ) {
				$content .= '<div class="studiofy-signed-box" style="border:2px solid #46b450; padding:20px; margin-top:30px; text-align:center; background:#f0f9f0;">';
				$content .= '<h3 style="color:#46b450;">Digitally Signed</h3>';
				$content .= '<img src="' . esc_url( $sig ) . '" alt="Signature" style="max-width:300px;">';
				$content .= '<p><small>Document Locked.</small></p>';
				$content .= '</div>';
			} else {
				// Show Pad
				$content .= '<div id="sig-area" style="margin-top:30px;">';
				$content .= '<p>Please sign below using your mouse or finger:</p>';
				$content .= '<canvas id="signature-pad" style="border:1px dashed #ccc; width:100%; height:200px; touch-action:none;"></canvas>';
				$content .= '<br><button id="clear-sig" class="button">Clear</button> ';
				$content .= '<button id="save-sig" class="button button-primary">Adopt & Sign</button>';
				$content .= '</div>';
			}
		}
		return $content;
	}

	/**
	 * Handle AJAX Signature Submission
	 */
	public function handle_signature_submission(): void {
		check_ajax_referer( 'studiofy_sign', 'security' );

		$id  = intval( $_POST['id'] );
		$sig = sanitize_text_field( $_POST['signature'] );

		// Save Signature
		update_post_meta( $id, '_studiofy_signature', $sig );
		
		// Lock Contract
		update_post_meta( $id, '_studiofy_status', 'Signed' );

		wp_send_json_success();
	}
}
