<?php
class Studiofy_Settings {
    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function register_settings() {
        register_setting( 'studiofy_option_group', 'studiofy_settings', array( $this, 'sanitize_callback' ) );
        
        add_settings_section( 'studiofy_api', 'API Credentials', null, 'studiofy-settings' );
        
        add_settings_field( 'google_client_id', 'Google Client ID', array($this, 'field_text'), 'studiofy-settings', 'studiofy_api', ['id'=>'google_client_id'] );
        add_settings_field( 'google_client_secret', 'Google Secret', array($this, 'field_password'), 'studiofy-settings', 'studiofy_api', ['id'=>'google_client_secret'] );
        add_settings_field( 'square_access_token', 'Square Token', array($this, 'field_password'), 'studiofy-settings', 'studiofy_api', ['id'=>'square_access_token'] );
        add_settings_field( 'square_location_id', 'Square Location ID', array($this, 'field_text'), 'studiofy-settings', 'studiofy_api', ['id'=>'square_location_id'] );
    }

    public function sanitize_callback( $input ) {
        $new = array();
        $enc = new Studiofy_Encryption();
        $old = get_option('studiofy_settings');

        $new['google_client_id'] = sanitize_text_field( $input['google_client_id'] );
        $new['square_location_id'] = sanitize_text_field( $input['square_location_id'] );

        // Encrypt Secrets
        if( !empty($input['google_client_secret']) ) $new['google_client_secret'] = $enc->encrypt( $input['google_client_secret'] );
        else $new['google_client_secret'] = $old['google_client_secret'] ?? '';

        if( !empty($input['square_access_token']) ) $new['square_access_token'] = $enc->encrypt( $input['square_access_token'] );
        else $new['square_access_token'] = $old['square_access_token'] ?? '';

        return $new;
    }

    public function field_text( $args ) {
        $opts = get_option('studiofy_settings');
        echo '<input type="text" name="studiofy_settings['.$args['id'].']" value="'.esc_attr($opts[$args['id']] ?? '').'" class="regular-text">';
    }

    public function field_password( $args ) {
        echo '<input type="password" name="studiofy_settings['.$args['id'].']" placeholder="Encrypted" class="regular-text">';
    }
}
