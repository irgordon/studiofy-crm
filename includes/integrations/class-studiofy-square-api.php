<?php
declare( strict_types=1 );

class Studiofy_Square_API {
	private string $access_token;
	private string $base_url;

	public function __construct() {
		require_once STUDIOFY_PATH . 'includes/utils/class-studiofy-encryption.php';
		$enc = new Studiofy_Encryption();
		$opts = get_option( 'studiofy_settings', array() );

		$this->access_token = ! empty( $opts['square_access_token'] ) ? $enc->decrypt( $opts['square_access_token'] ) : '';
		$is_prod = ( isset( $opts['square_environment'] ) && 'production' === $opts['square_environment'] );
		$this->base_url = $is_prod ? 'https://connect.squareup.com/v2' : 'https://connect.squareupsandbox.com/v2';
	}

	public function create_invoice( array $data ) {
		if ( empty( $this->access_token ) ) return new WP_Error( 'config', 'Missing Token' );

		$response = wp_remote_post( $this->base_url . '/invoices', array(
			'method' => 'POST',
			'body' => wp_json_encode( $data ),
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->access_token,
				'Content-Type' => 'application/json',
				'Square-Version' => '2023-10-20'
			),
			'timeout' => 45
		) );

		if ( is_wp_error( $response ) ) return $response;
		
		return json_decode( wp_remote_retrieve_body( $response ), true );
	}
}
