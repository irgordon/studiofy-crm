<?php

declare(strict_types=1);

use Google\Client;
use Google\Service\Calendar;

class Studiofy_Google_Calendar {
	private ?Client $client;
	private Studiofy_Encryption $encryption;

	public function __construct() {
		require_once STUDIOFY_PATH . 'includes/utils/class-encryption.php';
		$this->encryption = new Studiofy_Encryption();
		$this->init_client();
	}

	private function init_client(): void {
		$options = get_option( 'studiofy_settings', array() );

		$this->client = new Client();
		$this->client->setApplicationName( 'Studiofy CRM' );
		$this->client->setScopes( Calendar::CALENDAR_EVENTS );
		$this->client->setAccessType( 'offline' );
		$this->client->setPrompt( 'select_account consent' );

		$client_id        = $options['google_client_id'] ?? '';
		$encrypted_secret = $options['google_client_secret'] ?? '';
		$client_secret    = $this->encryption->decrypt( $encrypted_secret );

		if ( ! empty( $client_id ) && ! empty( $client_secret ) ) {
			$this->client->setClientId( $client_id );
			$this->client->setClientSecret( $client_secret );
			$this->client->setRedirectUri( admin_url( 'admin.php?page=studiofy-settings' ) );
		}
	}

	public function get_auth_url(): string {
		return $this->client->createAuthUrl();
	}

	public function is_connected(): bool {
		$token = get_option( 'studiofy_google_token' );
		return ! empty( $token );
	}
}
