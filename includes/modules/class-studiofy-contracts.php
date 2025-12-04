<?php
declare( strict_types=1 );
class Studiofy_Contracts {
	public function init(): void { add_action( 'template_redirect', array( $this, 'print_view' ) ); }
	public function print_view(): void {
		if ( isset( $_GET['print_contract'] ) && is_user_logged_in() ) {
			$post = get_post( intval( $_GET['print_contract'] ) );
			if ( ! $post || 'studiofy_contract' !== $post->post_type ) return;
			echo '<!DOCTYPE html><html><head><title>Contract</title><style>body{font-family:serif;padding:40px;max-width:800px;margin:0 auto}</style></head><body>';
			echo '<h1>'.esc_html($post->post_title).'</h1>'.wp_kses_post(wpautop($post->post_content));
			$sig = get_post_meta($post->ID, '_studiofy_signature', true);
			if($sig) echo '<br><img src="'.esc_url($sig).'" width="200"><p>Signed</p>';
			echo '</body></html>';
			exit;
		}
	}
}
