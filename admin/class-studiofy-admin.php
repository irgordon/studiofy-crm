<?php
declare(strict_types=1);

class Studiofy_Admin {
    private string $plugin_name;
    private string $version;

    public function __construct( string $plugin_name, string $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
    }

    public function enqueue_styles(): void {
        wp_enqueue_style( $this->plugin_name, STUDIOFY_URL . 'admin/css/studiofy-admin.css', array(), $this->version, 'all' );
    }

    /**
     * Enqueue Admin Scripts & Localize Data
     * Reference: https://developer.wordpress.org/plugins/javascript/enqueuing/
     */
    public function enqueue_scripts( string $hook ): void {
        // Only load JS on Studiofy pages
        if ( strpos( $hook, 'studiofy' ) === false ) {
            return;
        }

        wp_enqueue_script( 
            $this->plugin_name . '-admin', 
            STUDIOFY_URL . 'admin/js/studiofy-admin.js', 
            array( 'jquery', 'heartbeat' ), // Dependency on 'heartbeat' is crucial
            $this->version, 
            true 
        );

        // Pass PHP data to JS safely
        wp_localize_script( 
            $this->plugin_name . '-admin', 
            'studiofy_admin_vars', 
            array(
                'nonce' => wp_create_nonce( 'studiofy_admin_nonce' ),
                'strings' => array(
                    'confirm_delete' => __( 'Are you sure you want to delete this item? This cannot be undone.', 'studiofy-crm' )
                )
            ) 
        );
    }

    // ... [Menu Registration methods remain the same] ...

    /**
     * Heartbeat API Handler
     * Reference: https://developer.wordpress.org/plugins/javascript/heartbeat-api/
     */
    public function received_heartbeat( array $response, array $data ): array {
        // Check if our JS sent the request
        if ( empty( $data['studiofy_refresh_stats'] ) ) {
            return $response;
        }

        // Verify Nonce (Security)
        if ( ! wp_verify_nonce( $data['studiofy_refresh_stats'], 'studiofy_admin_nonce' ) ) {
            return $response;
        }

        // Fetch fresh stats
        global $wpdb;
        $leads  = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}studiofy_leads WHERE status='new'" );
        $unpaid = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}studiofy_invoices WHERE status='Unpaid'" );

        // Send back to JS
        $response['studiofy_stats'] = array(
            'leads'  => $leads,
            'unpaid' => $unpaid
        );

        return $response;
    }
}
