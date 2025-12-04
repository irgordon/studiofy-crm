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
        add_submenu_page( 'studiofy-crm', 'Add Client', 'Add New', 'edit_studiofy_client', 'studiofy-crm-new', array( $this, 'display_add_client' ) );
        add_submenu_page( 'studiofy-crm', 'Settings', 'Settings', 'manage_studiofy_settings', 'studiofy-settings', array( $this, 'display_settings' ) );
    }

    public function add_dashboard_widgets() {
        wp_add_dashboard_widget( 'studiofy_overview', 'Studiofy Studio Overview', array( $this, 'render_dashboard_widget' ) );
    }

    public function render_dashboard_widget() {
        $stats = get_transient( 'studiofy_stats' );
        if ( false === $stats ) {
            global $wpdb;
            $stats['leads'] = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}studiofy_clients WHERE status='lead'" );
            $stats['unpaid'] = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}studiofy_invoices WHERE status='unpaid'" );
            set_transient( 'studiofy_stats', $stats, 900 );
        }
        ?>
        <div class="studiofy-dashboard-grid">
            <div class="studiofy-card"><h3><?php echo intval($stats['leads']); ?></h3><small>Leads</small></div>
            <div class="studiofy-card"><h3><?php echo intval($stats['unpaid']); ?></h3><small>Unpaid Invoices</small></div>
        </div>
        <?php
    }

    // --- ASYNC INVOICE LOGIC ---
    public function process_generate_invoice() {
        if ( ! check_admin_referer( 'studiofy_invoice_action' ) ) wp_die('Security check failed');
        
        global $wpdb;
        $amount = floatval( $_POST['amount'] );
        $client_id = intval( $_POST['client_id'] );
        
        $wpdb->insert( $wpdb->prefix.'studiofy_invoices', [
            'client_id' => $client_id, 
            'status' => 'processing', 
            'amount' => $amount
        ]);
        $local_id = $wpdb->insert_id;

        $args = [ 'local_invoice_id' => $local_id, 'amount' => $amount ];
        wp_schedule_single_event( time(), 'studiofy_async_generate_invoice', array( $args ) );

        wp_redirect( admin_url('admin.php?page=studiofy-crm&queued=1') );
        exit;
    }

    public function execute_invoice_job( $args ) {
        global $wpdb;
        $local_id = $args['local_invoice_id'];

        try {
            require_once STUDIOFY_PATH . 'includes/integrations/class-studiofy-square-api.php';
            $square = new Studiofy_Square_API();
            $res = $square->generate_invoice( [], [], $args['amount'], '' );

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

    // --- VIEW LOADERS ---
    public function display_dashboard() {
        require_once STUDIOFY_PATH . 'admin/class-studiofy-client-list-table.php';
        $table = new Studiofy_Client_List_Table();
        $table->prepare_items();
        echo '<div class="wrap"><h1>Clients</h1><form method="get"><input type="hidden" name="page" value="studiofy-crm">';
        $table->search_box('Search', 'search_id');
        $table->display();
        echo '</form></div>';
    }

    public function display_add_client() {
        require_once STUDIOFY_PATH . 'admin/partials/studiofy-admin-add-client.php';
    }

    public function display_settings() {
        echo '<div class="wrap"><h1>Settings</h1><form method="post" action="options.php">';
        settings_fields( 'studiofy_option_group' );
        do_settings_sections( 'studiofy-settings' );
        submit_button();
        echo '</form></div>';
    }

    public function activation_success_notice() {
        if ( get_transient( 'studiofy_activation_redirect' ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>Studiofy CRM installed successfully.</p></div>';
            delete_transient( 'studiofy_activation_redirect' );
        }
    }
}
