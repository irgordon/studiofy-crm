<?php
declare( strict_types=1 );

class Studiofy_Metaboxes {
	public function init(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_boxes' ) );
		add_action( 'save_post', array( $this, 'save' ) );
	}

	public function add_boxes(): void {
		add_meta_box( 'studiofy_invoice_meta', 'Invoice Details', array( $this, 'render_invoice' ), 'studiofy_invoice', 'normal', 'high' );
	}

	public function render_invoice( WP_Post $post ): void {
		wp_nonce_field( 'studiofy_meta', 'studiofy_meta_nonce' );
		$amt = get_post_meta( $post->ID, '_studiofy_amount', true );
		echo '<label>Amount ($): </label><input type="number" name="studiofy_amount" value="' . esc_attr( $amt ) . '">';
	}

	public function save( int $post_id ): void {
		if ( ! isset( $_POST['studiofy_meta_nonce'] ) || ! wp_verify_nonce( $_POST['studiofy_meta_nonce'], 'studiofy_meta' ) ) return;
		if ( isset( $_POST['studiofy_amount'] ) ) {
			update_post_meta( $post_id, '_studiofy_amount', sanitize_text_field( $_POST['studiofy_amount'] ) );
		}
	}
}
