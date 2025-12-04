<?php
class Studiofy_Encryption {
    private $method = 'AES-256-CBC';
    private $key;

    public function __construct() {
        $this->key = defined('AUTH_KEY') ? AUTH_KEY : 'studiofy-fallback-salt';
    }

    public function encrypt( $data ) {
        $iv = openssl_random_pseudo_bytes( openssl_cipher_iv_length( $this->method ) );
        $encrypted = openssl_encrypt( $data, $this->method, $this->key, 0, $iv );
        return base64_encode( $encrypted . '::' . $iv );
    }

    public function decrypt( $data ) {
        if ( empty( $data ) ) return '';
        $payload = base64_decode( $data );
        if ( strpos( $payload, '::' ) === false ) return '';
        list( $encrypted_data, $iv ) = explode( '::', $payload, 2 );
        return openssl_decrypt( $encrypted_data, $this->method, $this->key, 0, $iv );
    }
}
