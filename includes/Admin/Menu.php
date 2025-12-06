<?php
/**
 * Admin Menu Controller
 * @package Studiofy\Admin
 * @version 2.1.8
 */

declare(strict_types=1);

namespace Studiofy\Admin;

use function Studiofy\studiofy_get_asset_version;

class Menu {

    private Settings $settings;
    private DashboardController $dashboardController;
    private ProjectController $projectController;
    private ContractController $contractController;
    private InvoiceController $invoiceController;
    private CustomerController $customerController;
    private BookingController $bookingController;
    private GalleryController $galleryController;

    public function __construct() {
        $this->settings = new Settings();
        $this->dashboardController = new DashboardController();
        $this->projectController = new ProjectController();
        $this->contractController = new ContractController();
        $this->invoiceController = new InvoiceController();
        $this->customerController = new CustomerController();
        $this->bookingController = new BookingController();
        $this->galleryController = new GalleryController();
    }

    public function init(): void {
        add_action('admin_menu', [$this, 'register_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
        
        $this->settings->init();
        $this->projectController->init();
        $this->contractController->init();
        $this->invoiceController->init();
        $this->customerController->init();
        $this->bookingController->init();
        $this->galleryController->init();
        
        add_filter('admin_footer_text', [$this, 'render_footer_version']);
    }

    public function enqueue_styles($hook): void {
        if (strpos($hook, 'studiofy') === false) return;

        wp_enqueue_style('dashicons');
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_media();

        wp_enqueue_style(
            'studiofy-admin-css', 
            STUDIOFY_URL . 'assets/css/admin.css', 
            [], 
            studiofy_get_asset_version('assets/css/admin.css')
        );

        // Specific CSS for Gallery Explorer
        if (strpos($hook, 'studiofy-galleries') !== false) {
            wp_enqueue_style(
                'studiofy-gallery-admin-css', 
                STUDIOFY_URL . 'assets/css/gallery-admin.css', 
                [], 
                studiofy_get_asset_version('assets/css/gallery-admin.css')
            );
        }

        wp_enqueue_script(
            'studiofy-admin-js', 
            STUDIOFY_URL . 'assets/js/admin.js', 
            ['jquery', 'jquery-ui-sortable', 'wp-color-picker'], 
            studiofy_get_asset_version('assets/js/admin.js'), 
            true
        );

        // Specific JS for Gallery Explorer
        if (strpos($hook, 'studiofy-galleries') !== false) {
            wp_enqueue_script(
                'studiofy-gallery-admin-js', 
                STUDIOFY_URL . 'assets/js/gallery-admin.js', 
                ['jquery', 'wp-api-fetch'], 
                studiofy_get_asset_version('assets/js/gallery-admin.js'), 
                true
            );
            wp_localize_script('studiofy-gallery-admin-js', 'studiofyGallerySettings', [
                'root' => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('wp_rest'),
                'max_upload_size' => wp_max_upload_size()
            ]);
        }
    }

    public function register_menu_pages(): void {
        add_menu_page('Studiofy CRM', 'Studiofy CRM', 'manage_options', 'studiofy-dashboard', [$this->dashboardController, 'render_page'], 'dashicons-camera', 6);
        add_submenu_page('studiofy-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'studiofy-dashboard', [$this->dashboardController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Customers', 'Customers', 'manage_options', 'studiofy-customers', [$this->customerController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Projects', 'Projects', 'manage_options', 'studiofy-projects', [$this->projectController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Contracts', 'Contracts', 'manage_options', 'studiofy-contracts', [$this->contractController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Invoices', 'Invoices', 'manage_options', 'studiofy-invoices', [$this->invoiceController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Appointments', 'Appointments', 'manage_options', 'studiofy-appointments', [$this->bookingController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Galleries', 'Galleries', 'manage_options', 'studiofy-galleries', [$this->galleryController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Settings', 'Settings', 'manage_options', 'studiofy-settings', [$this->settings, 'render_page']);
    }

    public function render_footer_version($text): string {
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'studiofy') !== false) {
            return 'Studiofy CRM <b>v' . esc_html(STUDIOFY_VERSION) . '</b>';
        }
        return $text;
    }
}
