<?php

declare(strict_types=1);

class Studiofy_Public {

	private string $version;

	public function __construct( string $plugin_name, string $version ) {
		$this->version = $version;
	}

	public function enqueue_scripts(): void {
		global $post;
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'studiofy_contract' ) ) {
			wp_enqueue_script( 'sig-pad', 'https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js', array(), '4.0', true );
			wp_enqueue_script( 'studiofy-js', STUDIOFY_URL . 'public/js/studiofy-public.js', array( 'jquery', 'sig-pad' ), $this->version, true );
			wp_localize_script(
				'studiofy-js',
				'studiofy_vars',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'studiofy_sign' ),
				)
			);
		}
	}

	public function render_contract_shortcode( array $atts ): string {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'studiofy_contract' );
		$id   = intval( $atts['id'] );
		$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}studiofy_contracts WHERE id=%d", $id ) );

		if ( ! $row ) {
			return '<p>' . esc_html__( 'Contract not found.', 'studiofy-crm' ) . '</p>';
		}

		if ( ! current_user_can( 'manage_studiofy_contracts' ) && $row->access_token !== $token ) {
			return '<p class="error">' . esc_html__( 'Access Denied. Invalid Token.', 'studiofy-crm' ) . '</p>';
		}

		ob_start();
		echo '<div class="contract-wrapper" style="max-width:800px; margin:0 auto;">';
		echo '<div class="contract-content">' . wp_kses_post( $row->content ) . '</div>';
		echo '<hr>';
		echo '<h3>' . esc_html__( 'Sign Below', 'studiofy-crm' ) . '</h3>';
		echo '<canvas id="signature-pad" style="border:1px dashed #ccc; width:100%; height:200px;"></canvas>';
		echo '<button id="save-sig" class="button">' . esc_html__( 'Agree & Sign', 'studiofy-crm' ) . '</button>';
		echo '<input type="hidden" id="cid" value="' . esc_attr( (string)$id ) . '">';
		echo '<input type="hidden" id="ctoken" value="' . esc_attr( $row->access_token ) . '">';
		echo '</div>';

		return (string) ob_get_clean();
	}

	public function handle_signature_submission(): void {
		check_ajax_referer( 'studiofy_sign', 'security' );

		$id    = intval( $_POST['id'] ?? 0 );
		$token = sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) );
		$sig   = sanitize_text_field( wp_unslash( $_POST['signature'] ?? '' ) );

		global $wpdb;
		$valid = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}studiofy_contracts WHERE id=%d AND access_token=%s", $id, $token ) );

		if ( $valid ) {
			$wpdb->update(
				$wpdb->prefix . 'studiofy_contracts',
				array(
					'signature_data' => $sig,
					'status'         => 'signed',
					'signed_ip'      => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
				),
				array( 'id' => $id )
			);
			wp_send_json_success();
		} else {
			wp_send_json_error( __( 'Invalid Token', 'studiofy-crm' ) );
		}
	}
}
