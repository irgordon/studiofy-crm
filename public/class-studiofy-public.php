<?php
declare( strict_types=1 );

class Studiofy_Public {
	private string $version;
	public function __construct( string $plugin_name, string $version ) {
		$this->version = $version;
	}

	public function enqueue_scripts(): void {
		global $post;
		if ( is_a( $post, 'WP_Post' ) && 'studiofy_contract' === $post->post_type ) {
			wp_enqueue_script( 'sig-pad', 'https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js', array(), '4.0', true );
			wp_enqueue_script( 'studiofy-public', STUDIOFY_URL . 'public/js/studiofy-public.js', array( 'jquery', 'sig-pad' ), $this->version, true );
			wp_localize_script( 'studiofy-public', 'studiofy_vars', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'studiofy_sign' ) ) );
		}
	}

	public function init(): void {
		add_filter( 'the_content', array( $this, 'append_signature_pad' ) );
	}

	public function append_signature_pad( string $content ): string {
		if ( is_singular( 'studiofy_contract' ) ) {
			$sig = get_post_meta( get_the_ID(), '_studiofy_signature', true );
			if ( $sig ) {
				$content .= '<div class="signed">Digitally Signed</div>';
			} else {
				$content .= '<canvas id="signature-pad" style="border:1px solid #ccc; width:100%; height:200px;"></canvas><button id="save-sig">Sign</button>';
			}
		}
		return $content;
	}

	public function handle_signature_submission(): void {
		check_ajax_referer( 'studiofy_sign', 'security' );
		$id = intval( $_POST['id'] );
		$sig = sanitize_text_field( $_POST['signature'] );
		update_post_meta( $id, '_studiofy_signature', $sig );
		wp_send_json_success();
	}
}
