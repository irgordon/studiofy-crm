<?php
use Square\SquareClient;
use Square\Environment;

class Studiofy_Square_API {

    private $client;
    private $location_id;
    
    public function __construct() {
        require_once STUDIOFY_PATH . 'includes/utils/class-encryption.php';
        $encryption = new Studiofy_Encryption();
        $options = get_option( 'studiofy_settings' );

        // 1. Get Environment
        $env_setting = isset( $options['square_environment'] ) ? $options['square_environment'] : 'sandbox';
        $env = ( $env_setting === 'production' ) ? Environment::PRODUCTION : Environment::SANDBOX;

        // 2. Get Location ID
        $this->location_id = isset( $options['square_location_id'] ) ? $options['square_location_id'] : '';

        // 3. Get & Decrypt Token
        $encrypted_token = isset( $options['square_access_token'] ) ? $options['square_access_token'] : '';
        $access_token = $encryption->decrypt( $encrypted_token );

        // 4. Initialize Client
        if ( $access_token ) {
            $this->client = new SquareClient([
                'accessToken' => $access_token,
                'environment' => $env,
            ]);
        }
    }

    // ... (generate_invoice method uses $this->location_id and $this->client) ...
}
