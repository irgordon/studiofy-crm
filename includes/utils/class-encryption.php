<?php
declare( strict_types=1 );
class Studiofy_Encryption {
	private string $key;
	public function __construct() { $this->key = defined('AUTH_KEY') ? AUTH_KEY : 'studiofy-salt'; }
	public function encrypt( string $data ): string {
		if(empty($data)) return '';
		$iv = openssl_random_pseudo_bytes(16);
		return base64_encode(openssl_encrypt($data, 'AES-256-CBC', $this->key, 0, $iv) . '::' . $iv);
	}
	public function decrypt( string $data ): string {
		if(empty($data)) return '';
		$p = base64_decode($data);
		if(strpos($p, '::')===false) return '';
		list($e, $iv) = explode('::', $p, 2);
		return openssl_decrypt($e, 'AES-256-CBC', $this->key, 0, $iv);
	}
}
