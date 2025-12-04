<?php
declare(strict_types=1);

class Studiofy_Core {
    protected string $plugin_name = 'studiofy-crm';
    protected string $version = STUDIOFY_VERSION;

    public function run(): void {
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function define_admin_hooks(): void {
        require_once STUDIOFY_PATH . 'admin/class-studiofy-admin.php';
        require_once STUDIOFY_PATH . 'admin/class-studiofy-settings.php';
        
        $plugin_admin = new Studiofy_Admin( $this->plugin_name, $this->version );
        new Studiofy_Settings();

        add_action( 'admin_menu', array( $plugin_admin, 'add_plugin_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );
        
        // CRUD Actions (POST Handlers)
        add_action( 'admin_post_studiofy_save_project', array( $plugin_admin, 'handler_save_project' ) );
        add_action( 'admin_post_studiofy_delete_project', array( $plugin_admin, 'handler_delete_project' ) );
        add_action( 'admin_post_studiofy_save_invoice', array( $plugin_admin, 'handler_save_invoice' ) );
        add_action( 'admin_post_studiofy_delete_item', array( $plugin_admin, 'handler_delete_item' ) ); // Generic deleter
    }

    private function define_public_hooks(): void {
        require_once STUDIOFY_PATH . 'public/class-studiofy-public.php';
        $plugin_public = new Studiofy_Public( $this->plugin_name, $this->version );
        
        add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_scripts' ) );
        add_shortcode( 'studiofy_lead_form', array( $plugin_public, 'render_lead_shortcode' ) );
        add_action( 'admin_post_studiofy_capture_lead', array( $plugin_public, 'process_lead_form' ) );
        add_action( 'admin_post_nopriv_studiofy_capture_lead', array( $plugin_public, 'process_lead_form' ) );
    }
}
