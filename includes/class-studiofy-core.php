<?php
class Studiofy_Core {
    protected $loader;
    protected $plugin_name = 'studiofy-crm';
    protected $version = STUDIOFY_VERSION;

    public function run() {
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function define_admin_hooks() {
        require_once STUDIOFY_PATH . 'admin/class-studiofy-admin.php';
        require_once STUDIOFY_PATH . 'admin/class-studiofy-settings.php';
        
        $plugin_admin = new Studiofy_Admin( $this->plugin_name, $this->version );
        $plugin_settings = new Studiofy_Settings(); // Auto-registers via constructor

        add_action( 'admin_menu', array( $plugin_admin, 'add_plugin_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );
        add_action( 'admin_notices', array( $plugin_admin, 'activation_success_notice' ) );
        
        // Dashboard Widget
        add_action( 'wp_dashboard_setup', array( $plugin_admin, 'add_dashboard_widgets' ) );
        
        // Async Job Hook
        add_action( 'studiofy_async_generate_invoice', array( $plugin_admin, 'execute_invoice_job' ) );
        
        // Admin Post Actions
        add_action( 'admin_post_studiofy_save_contract', array( $plugin_admin, 'process_save_contract' ) );
    }

    private function define_public_hooks() {
        require_once STUDIOFY_PATH . 'public/class-studiofy-public.php';
        $plugin_public = new Studiofy_Public( $this->plugin_name, $this->version );

        add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_scripts' ) );
        add_shortcode( 'studiofy_contract', array( $plugin_public, 'render_contract_shortcode' ) );
        
        // AJAX Signatures
        add_action( 'wp_ajax_studiofy_submit_signature', array( $plugin_public, 'handle_signature_submission' ) );
        add_action( 'wp_ajax_nopriv_studiofy_submit_signature', array( $plugin_public, 'handle_signature_submission' ) );
    }
}
