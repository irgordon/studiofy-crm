<?php
class Studiofy_Settings {
    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function register_settings() {
        register_setting( 'studiofy_options', 'studiofy_settings', array( $this, 'sanitize' ) );
        
        add_settings_section( 'studiofy_api', 'API Keys', null, 'studiofy-settings' );
        
        add_settings_field( 'square_token', 'Square Token', array($this, 'field_password'), 'studiofy-settings', 'studiofy_api', ['key'=>'square_token'] );
        add_settings_field( 'google_id', 'Google Client ID', array($this, 'field_text'), 'studiofy-settings', 'studiofy_api', ['key'=>'google_id'] );
    }

    public function sanitize( $input ) {
        $new = array();
        $enc = new Studiofy_Encryption();
        
        // Handle Square Token Encryption
        if( !empty($input['square_token']) ) {
            $new['square_token'] = $enc->encrypt( sanitize_text_field($input['square_token']) );
        } else {
            $old = get_option('studiofy_settings');
            $new['square_token'] = $old['square_token'] ?? '';
        }
        return $new;
    }

    public function field_text( $args ) {
        $opts = get_option('studiofy_settings');
        echo '<input type="text" name="studiofy_settings['.$args['key'].']" value="'.esc_attr($opts[$args['key']] ?? '').'" class="regular-text">';
    }

    public function field_password( $args ) {
        echo '<input type="password" name="studiofy_settings['.$args['key'].']" placeholder="Encrypted" class="regular-text">';
    }
}
