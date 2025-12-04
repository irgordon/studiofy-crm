<?php
use Square\SquareClient;
use Square\Environment;
use Square\Models\Money;
use Square\Models\Currency;

class Studiofy_Square_API {
    private $client;
    
    public function __construct() {
        require_once STUDIOFY_PATH . 'includes/utils/class-encryption.php';
        $enc = new Studiofy_Encryption();
        $options = get_option( 'studiofy_settings' );
        
        $token = isset($options['square_token']) ? $enc->decrypt($options['square_token']) : '';
        $env = Environment::SANDBOX; // Toggle via settings in real app

        if ( $token ) {
            $this->client = new SquareClient([
                'accessToken' => $token,
                'environment' => $env,
            ]);
        }
    }

    public function generate_invoice( $client_data, $booking_data, $amount, $due_date ) {
        if ( ! $this->client ) return new WP_Error( 'config_error', 'Square not configured' );

        // Simplified logic for brevity - Real implementation includes Customer/Order creation steps
        try {
            // Mock Success for structure demonstration
            // In production, insert CreateOrderRequest and CreateInvoiceRequest here
            return array(
                'id' => 'inv_' . uniqid(),
                'url' => 'https://sandbox.square.site/invoice/' . uniqid()
            );
        } catch ( Exception $e ) {
            return new WP_Error( 'api_error', $e->getMessage() );
        }
    }
}
