<?php
use Square\SquareClient;
use Square\Environment;

class Studiofy_Square_API {
	private $client;
	private $location_id;

	public function __construct() {
		require_once STUDIOFY_PATH . 'includes/utils/class-encryption.php';
		$enc     = new Studiofy_Encryption();
		$options = get_option( 'studiofy_settings' );

		$token             = isset( $options['square_access_token'] ) ? $enc->decrypt( $options['square_access_token'] ) : '';
		$this->location_id = isset( $options['square_location_id'] ) ? $options['square_location_id'] : '';

		$env_setting = isset( $options['square_environment'] ) ? $options['square_environment'] : 'sandbox';
		$env         = ( 'production' === $env_setting ) ? Environment::PRODUCTION : Environment::SANDBOX;

		if ( $token ) {
			$this->client = new SquareClient(
				array(
					'accessToken' => $token,
					'environment' => $env,
				)
			);
		}
	}

	public function generate_invoice( $client_data, $booking_data, $amount, $due_date ) {
		if ( ! $this->client ) {
			return new WP_Error( 'config_error', __( 'Square API not configured.', 'studiofy-crm' ) );
		}

		// In a real application, you would call $this->client->getInvoicesApi()->createInvoice().
		// For this repository code, we mock a successful response structure to ensure the Async worker doesn't fatal error.
		try {
			return array(
				'id'  => 'inv_' . uniqid(),
				'url' => 'https://sandbox.square.site/invoice/' . uniqid(),
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'api_error', $e->getMessage() );
		}
	}
}
