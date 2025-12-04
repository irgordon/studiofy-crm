<?php
use Google\Client;
use Google\Service\Calendar;

class Studiofy_Google_Calendar {
	private $client;
	private $encryption;

	public function __construct() {
		require_once STUDIOFY_PATH . 'includes/utils/class-encryption.php';
		$this->encryption = new Studiofy_Encryption();
		$this->init_client();
	}

	private function init_client() {
		$options = get_option( 'studiofy_settings' );

		$this->client = new Client();
		$this->client->setApplicationName( 'Studiofy CRM' );
		$this->client->setScopes( Calendar::CALENDAR_EVENTS );
		$this->client->setAccessType( 'offline' );
		$this->client->setPrompt( 'select_account consent' );

		$client_id = isset( $options['google_client_id'] ) ? $options['google_client_id'] : '';
		
		$encrypted_secret = isset( $options['google_client_secret'] ) ? $options['google_client_secret'] : '';
		$client_secret    = $this->encryption->decrypt( $encrypted_secret );

		if ( $client_id && $client_secret ) {
			$this->client->setClientId( $client_id );
			$this->client->setClientSecret( $client_secret );
			$this->client->setRedirectUri( admin_url( 'admin.php?page=studiofy-settings' ) );
		}
	}

	public function get_auth_url() {
		return $this->client->createAuthUrl();
	}

	public function is_connected() {
		$token = get_option( 'studiofy_google_token' );
		return ! empty( $token );
	}
}
