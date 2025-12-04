<?php
class Studiofy_Admin {
    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, STUDIOFY_URL . 'admin/css/studiofy-admin.css', array(), $this->version, 'all' );
    }

    public function add_plugin_admin_menu() {
        add_menu_page( 'Studiofy CRM', 'Studiofy CRM', 'view_studiofy_crm', 'studiofy-crm', array( $this, 'display_dashboard' ), 'dashicons-camera', 6 );
        add_submenu_page( 'studiofy-crm', 'Add Client', 'Add New', 'edit_studiofy_client', 'studiofy-new', array( $this, 'display_add_client' ) );
        add_submenu_page( 'studiofy-crm', 'Settings', 'Settings', 'manage_studiofy_settings', 'studiofy-settings', array( $this, 'display_settings' ) );
    }

    /**
     * Dashboard with Transients
     */
    public function add_dashboard_widgets() {
        wp_add_dashboard_widget( 'studiofy_overview', 'Studiofy Overview', array( $this, 'render_dashboard_widget' ) );
    }

    public function render_dashboard_widget() {
        $stats = get_transient( 'studiofy_stats' );
        if ( false === $stats ) {
            global $wpdb;
            $stats['leads'] = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}studiofy_clients WHERE status='lead'" );
            set_transient( 'studiofy_stats', $stats, 900 );
        }
        echo '<div class="studiofy-card"><h3>Leads: ' . intval($stats['leads']) . '</h3></div>';
    }

    /**
     * Async Invoice Job (Anti-WSOD)
     */
    public function execute_invoice_job( $args ) {
        global $wpdb;
        $local_id = $args['local_invoice_id'];

        try {
            require_once STUDIOFY_PATH . 'includes/integrations/class-studiofy-square-api.php';
            $square = new Studiofy_Square_API();
            $res = $square->generate_invoice( $args['client'], [], $args['amount'], $args['date'] );

            if ( is_wp_error( $res ) ) throw new Exception( $res->get_error_message() );

            $wpdb->update( 
                $wpdb->prefix.'studiofy_invoices', 
                ['status' => 'unpaid', 'square_invoice_id' => $res['id'], 'invoice_url' => $res['url']], 
                ['id' => $local_id] 
            );

        } catch ( Exception $e ) {
            $wpdb->update( 
                $wpdb->prefix.'studiofy_invoices', 
                ['status' => 'failed', 'notes' => substr($e->getMessage(), 0, 255)], 
                ['id' => $local_id] 
            );
        }
    }

    // Displays
    public function display_dashboard() { include_once STUDIOFY_PATH . 'admin/partials/dashboard-view.php'; }
    public function display_add_client() { include_once STUDIOFY_PATH . 'admin/partials/add-client-view.php'; }
    public function display_settings() { 
        echo '<div class="wrap"><h1>Settings</h1><form method="post" action="options.php">';
        settings_fields( 'studiofy_options' );
        do_settings_sections( 'studiofy-settings' );
        submit_button();
        echo '</form></div>';
    }
    
    public function activation_success_notice() {
        if ( get_transient( 'studiofy_activation_redirect' ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>Studiofy CRM Installed Successfully.</p></div>';
            delete_transient( 'studiofy_activation_redirect' );
        }
    }
}
