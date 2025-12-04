<?php

class Studiofy_Settings {

    private $option_name = 'studiofy_settings';

    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
    }

    /**
     * Add Settings Page to Admin Menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'studiofy-crm',           // Parent slug
            'Studiofy Settings',      // Page Title
            'Settings',               // Menu Title
            'manage_studiofy_settings', // Capability (Custom Cap)
            'studiofy-settings',      // Menu slug
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register Sections and Fields
     */
    public function register_settings() {
        // Register the database option group
        register_setting(
            'studiofy_option_group',
            $this->option_name,
            array( $this, 'sanitize_callback' )
        );

        // --- SECTION 1: Google Calendar ---
        add_settings_section(
            'studiofy_google_section',
            'Google Calendar Integration',
            array( $this, 'render_google_section_info' ),
            'studiofy-settings'
        );

        add_settings_field(
            'google_client_id',
            'Client ID',
            array( $this, 'render_text_field' ),
            'studiofy-settings',
            'studiofy_google_section',
            array( 'id' => 'google_client_id', 'desc' => 'From Google Cloud Console (OAuth 2.0 Client IDs)' )
        );

        add_settings_field(
            'google_client_secret',
            'Client Secret',
            array( $this, 'render_password_field' ),
            'studiofy-settings',
            'studiofy_google_section',
            array( 'id' => 'google_client_secret', 'desc' => 'The secret key associated with your Client ID.' )
        );

        // --- SECTION 2: Square Invoices ---
        add_settings_section(
            'studiofy_square_section',
            'Square Payments API',
            array( $this, 'render_square_section_info' ),
            'studiofy-settings'
        );

        add_settings_field(
            'square_access_token',
            'Personal Access Token',
            array( $this, 'render_password_field' ),
            'studiofy-settings',
            'studiofy_square_section',
            array( 'id' => 'square_access_token', 'desc' => 'From Square Developer Dashboard > Credentials.' )
        );

        add_settings_field(
            'square_location_id',
            'Location ID',
            array( $this, 'render_text_field' ),
            'studiofy-settings',
            'studiofy_square_section',
            array( 'id' => 'square_location_id', 'desc' => 'Found in Square Dashboard > Locations.' )
        );

        add_settings_field(
            'square_environment',
            'Environment',
            array( $this, 'render_select_field' ),
            'studiofy-settings',
            'studiofy_square_section',
            array( 
                'id' => 'square_environment', 
                'options' => array( 'sandbox' => 'Sandbox (Test)', 'production' => 'Production (Live)' ) 
            )
        );
    }

    /**
     * Sanitization & Encryption Callback
     */
    public function sanitize_callback( $input ) {
        $new_input = array();
        $options = get_option( $this->option_name );
        $encryption = new Studiofy_Encryption();

        // 1. Google Fields
        $new_input['google_client_id'] = sanitize_text_field( $input['google_client_id'] );
        
        // Handle Secret (Encrypt if changed, keep old if empty)
        if ( ! empty( $input['google_client_secret'] ) ) {
            $new_input['google_client_secret'] = $encryption->encrypt( sanitize_text_field( $input['google_client_secret'] ) );
        } else {
            $new_input['google_client_secret'] = isset($options['google_client_secret']) ? $options['google_client_secret'] : '';
        }

        // 2. Square Fields
        $new_input['square_location_id'] = sanitize_text_field( $input['square_location_id'] );
        $new_input['square_environment'] = sanitize_text_field( $input['square_environment'] );

        // Handle Token (Encrypt if changed)
        if ( ! empty( $input['square_access_token'] ) ) {
            $new_input['square_access_token'] = $encryption->encrypt( sanitize_text_field( $input['square_access_token'] ) );
        } else {
            $new_input['square_access_token'] = isset($options['square_access_token']) ? $options['square_access_token'] : '';
        }

        return $new_input;
    }

    /**
     * Render Methods (HTML Output)
     */
    public function render_text_field( $args ) {
        $options = get_option( $this->option_name );
        $val = isset( $options[ $args['id'] ] ) ? $options[ $args['id'] ] : '';
        echo '<input type="text" name="studiofy_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $val ) . '" class="regular-text">';
        if ( isset( $args['desc'] ) ) echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
    }

    public function render_password_field( $args ) {
        // We never show the decrypted secret back to the UI for security
        echo '<input type="password" name="studiofy_settings[' . esc_attr( $args['id'] ) . ']" value="" class="regular-text" placeholder="Start typing to update...">';
        if ( isset( $args['desc'] ) ) echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
    }

    public function render_select_field( $args ) {
        $options = get_option( $this->option_name );
        $val = isset( $options[ $args['id'] ] ) ? $options[ $args['id'] ] : '';
        echo '<select name="studiofy_settings[' . esc_attr( $args['id'] ) . ']">';
        foreach ( $args['options'] as $key => $label ) {
            $selected = ( $val === $key ) ? 'selected' : '';
            echo '<option value="' . esc_attr( $key ) . '" ' . $selected . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
    }

    public function render_google_section_info() {
        $redirect_uri = admin_url( 'admin.php?page=studiofy-settings' );
        echo '<p>To enable booking synchronization, create a Project in <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a>.</p>';
        echo '<p class="description"><strong>IMPORTANT:</strong> Set your "Authorized Redirect URI" to: <code>' . esc_url( $redirect_uri ) . '</code></p>';
    }

    public function render_square_section_info() {
        echo '<p>To enable invoicing, create an application in the <a href="https://developer.squareup.com/console/" target="_blank">Square Developer Console</a>.</p>';
    }

    /**
     * Render the Full Page
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_studiofy_settings' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'studiofy_option_group' );
                do_settings_sections( 'studiofy-settings' );
                submit_button( 'Save API Keys' );
                ?>
            </form>
            
            <hr>
            
            <h2>Status</h2>
            <?php 
            require_once STUDIOFY_PATH . 'includes/integrations/class-studiofy-google-calendar.php';
            $gcal = new Studiofy_Google_Calendar();
            
            if ( $gcal->is_connected() ) {
                echo '<p><span class="dashicons dashicons-yes" style="color:green"></span> Google Calendar is Connected.</p>';
                echo '<form method="post"><input type="hidden" name="studiofy_disconnect_google" value="1"><button class="button">Disconnect Google</button></form>';
            } elseif ( get_option( 'studiofy_settings' )['google_client_id'] ) {
                echo '<a href="' . esc_url( $gcal->get_auth_url() ) . '" class="button button-primary">Connect Google Calendar</a>';
            } else {
                echo '<p><em>Enter your API Keys above to connect services.</em></p>';
            }
            ?>
        </div>
        <?php
    }
}
