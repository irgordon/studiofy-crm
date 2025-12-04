<?php
declare( strict_types=1 );
class Studiofy_Public {
	private string $ver;
	public function __construct( string $name, string $ver ) { $this->ver = $ver; }
	public function enqueue_scripts(): void {
		global $post;
		if ( is_a( $post, 'WP_Post' ) && 'studiofy_contract' === $post->post_type ) {
			wp_enqueue_script( 'sig-pad', 'https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js', [], '4.0', true );
			wp_enqueue_script( 'studiofy-pub', STUDIOFY_URL.'public/js/studiofy-public.js', ['jquery','sig-pad'], $this->ver, true );
			wp_localize_script( 'studiofy-pub', 'studiofy_vars', ['ajax_url'=>admin_url('admin-ajax.php'), 'nonce'=>wp_create_nonce('studiofy_sign'), 'post_id'=>$post->ID] );
		}
	}
	public function init(): void { add_filter( 'the_content', array( $this, 'sig_pad' ) ); }
	public function sig_pad( string $c ): string {
		if ( is_singular( 'studiofy_contract' ) ) {
			$sig = get_post_meta( get_the_ID(), '_studiofy_signature', true );
			if ( $sig ) $c .= '<div>Signed</div><img src="'.esc_url($sig).'">';
			else $c .= '<canvas id="signature-pad" style="border:1px solid #ccc;width:100%;height:200px;"></canvas><button id="save-sig">Sign</button>';
		}
		return $c;
	}
	public function handle_signature_submission(): void {
		check_ajax_referer( 'studiofy_sign', 'security' );
		update_post_meta( intval($_POST['id']), '_studiofy_signature', sanitize_text_field($_POST['signature']) );
		wp_send_json_success();
	}
}
