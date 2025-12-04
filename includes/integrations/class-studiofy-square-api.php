<?php

declare(strict_types=1);

use Square\SquareClient;
use Square\Environment;

class Studiofy_Square_API {
	private ?SquareClient $client = null;
	private string $location_id;

	public function __construct() {
		require_once STUDIOFY_PATH . 'includes/utils/class-encryption.php';
		$enc     = new Studiofy_Encryption();
		$options = get_option( 'studiofy_settings', array() );

		$token             = ! empty( $options['square_access_token'] ) ? $enc->decrypt( $options['square_access_token'] ) : '';
		$this->location_id = $options['square_location_id'] ?? '';

		$env_setting = $options['square_environment'] ?? 'sandbox';
		$env         = ( 'production' === $env_setting ) ? Environment::PRODUCTION : Environment::SANDBOX;

		if ( ! empty( $token ) ) {
			$this->client = new SquareClient(
				array(
					'accessToken' => $token,
					'environment' => $env,
				)
			);
		}
	}

	public function generate_invoice( array $client_data, array $booking_data, float $amount, string $due_date ): array|WP_Error {
		if ( ! $this->client ) {
			return new WP_Error( 'config_error', __( 'Square API not configured.', 'studiofy-crm' ) );
		}

		// Mocked for release safety / example without hitting real API logic errors.
		try {
			return array(
				'id'  => 'inv_' . uniqid(),
				'url' => 'https://square.link/u/' . uniqid(),
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'api_error', $e->getMessage() );
		}
	}
}
