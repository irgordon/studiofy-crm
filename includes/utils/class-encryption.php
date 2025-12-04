<?php

declare(strict_types=1);

class Studiofy_Encryption {
	private string $method = 'AES-256-CBC';
	private string $key;

	public function __construct() {
		$this->key = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'studiofy-fallback-salt';
	}

	public function encrypt( string $data ): string {
		if ( empty( $data ) ) {
			return '';
		}
		$iv        = openssl_random_pseudo_bytes( openssl_cipher_iv_length( $this->method ) );
		$encrypted = openssl_encrypt( $data, $this->method, $this->key, 0, $iv );
		return base64_encode( $encrypted . '::' . $iv );
	}

	public function decrypt( string $data ): string {
		if ( empty( $data ) ) {
			return '';
		}
		$payload = base64_decode( $data );
		if ( false === strpos( $payload, '::' ) ) {
			return '';
		}
		list( $encrypted_data, $iv ) = explode( '::', $payload, 2 );
		return openssl_decrypt( $encrypted_data, $this->method, $this->key, 0, $iv );
	}
}
