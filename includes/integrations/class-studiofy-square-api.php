<?php
declare( strict_types=1 );
class Studiofy_Square_API {
	private string $token;
	private string $url;
	public function __construct() {
		require_once STUDIOFY_PATH . 'includes/utils/class-studiofy-encryption.php';
		$enc = new Studiofy_Encryption();
		$opt = get_option('studiofy_settings');
		$this->token = !empty($opt['square_access_token']) ? $enc->decrypt($opt['square_access_token']) : '';
		$this->url = (isset($opt['square_environment']) && 'production'===$opt['square_environment']) ? 'https://connect.squareup.com/v2' : 'https://connect.squareupsandbox.com/v2';
	}
	public function create_invoice(array $data) {
		if(empty($this->token)) return new WP_Error('config', 'Missing Token');
		$res = wp_remote_post($this->url . '/invoices', array(
			'method'=>'POST', 'body'=>wp_json_encode($data), 'headers'=>array('Authorization'=>'Bearer '.$this->token, 'Content-Type'=>'application/json'), 'timeout'=>45
		));
		return is_wp_error($res) ? $res : json_decode(wp_remote_retrieve_body($res), true);
	}
}
