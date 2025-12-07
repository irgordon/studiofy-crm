<?php
/**
 * Admin Menu Controller
 * @package Studiofy\Admin
 * @version 2.2.38
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
        add_action('admin_init', [$this, 'activation_redirect']);
        
        $this->settings->init();
        $this->projectController->init();
        $this->contractController->init();
        $this->invoiceController->init();
        $this->customerController->init();
        $this->bookingController->init();
        $this->galleryController->init();
        
        add_filter('admin_footer_text', [$this, 'render_footer_version']);
    }

    public function activation_redirect(): void {
        if (get_option('studiofy_do_activation_redirect', false)) {
            delete_option('studiofy_do_activation_redirect');
            if(!isset($_GET['activate-multi'])) {
                wp_redirect(admin_url('admin.php?page=studiofy-welcome'));
                exit;
            }
        }
    }

    public function register_menu_pages(): void {
        // FIX: Explicitly cast all strings to prevent null deprecations
        add_menu_page('Studiofy CRM', 'Studiofy CRM', 'manage_options', 'studiofy-dashboard', [$this->dashboardController, 'render_page'], 'dashicons-camera', 6);
        add_submenu_page('studiofy-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'studiofy-dashboard', [$this->dashboardController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Customers', 'Customers', 'manage_options', 'studiofy-customers', [$this->customerController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Projects', 'Projects', 'manage_options', 'studiofy-projects', [$this->projectController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Contracts', 'Contracts', 'manage_options', 'studiofy-contracts', [$this->contractController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Invoices', 'Invoices', 'manage_options', 'studiofy-invoices', [$this->invoiceController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Appointments', 'Appointments', 'manage_options', 'studiofy-appointments', [$this->bookingController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Galleries', 'Galleries', 'manage_options', 'studiofy-galleries', [$this->galleryController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Settings', 'Settings', 'manage_options', 'studiofy-settings', [$this->settings, 'render_page']);
        
        // Welcome Page (Parent is explicitly null)
        add_submenu_page(null, 'Welcome', 'Welcome', 'manage_options', 'studiofy-welcome', [$this, 'render_welcome_page']);
    }

    public function render_welcome_page(): void {
        ?>
        <div class="wrap studiofy-welcome-wrap">
            <div class="studiofy-welcome-panel">
                <div class="studiofy-logo" style="margin-bottom: 20px;">
                    <svg width="120" height="120" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="blueGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#4facfe;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#00f2fe;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                        <circle cx="50" cy="50" r="45" fill="#1d2327" />
                        <circle cx="50" cy="50" r="35" fill="none" stroke="#fff" stroke-width="2" />
                        <circle cx="50" cy="50" r="25" fill="url(#blueGradient)" />
                        <circle cx="65" cy="35" r="5" fill="rgba(255,255,255,0.8)" />
                    </svg>
                </div>

                <h1>Welcome to Studiofy CRM 📸</h1>
                <p class="about-text">Get started by setting up your environment. Would you like to import demo data to see how the system works?</p>
                <p>This will create sample Customers, Projects, Invoices, Contracts, and Galleries.</p>
                
                <div class="studiofy-welcome-actions">
                    <a href="<?php echo esc_url(admin_url('admin-post.php?action=studiofy_internal_import&nonce='.wp_create_nonce('internal_import'))); ?>" class="button button-primary button-hero">Yes, Import Demo Data</a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=studiofy-dashboard')); ?>" class="button button-secondary button-hero">No, Skip to Dashboard</a>
                </div>
            </div>
        </div>
        <style>
            .studiofy-welcome-wrap { display: flex; justify-content: center; align-items: center; height: 80vh; background: #f0f0f1; }
            .studiofy-welcome-panel { background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); text-align: center; max-width: 600px; }
            .studiofy-welcome-panel h1 { font-size: 32px; margin-bottom: 20px; }
            .studiofy-welcome-actions { margin-top: 30px; display: flex; gap: 20px; justify-content: center; }
        </style>
        <?php
    }

    public function enqueue_styles($hook): void {
        if (strpos((string)$hook, 'studiofy') === false) return;

        wp_enqueue_style('dashicons');
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_media();

        wp_enqueue_style('studiofy-admin-css', STUDIOFY_URL . 'assets/css/admin.css', [], studiofy_get_asset_version('assets/css/admin.css'));

        if (strpos($hook, 'studiofy-galleries') !== false) {
            wp_enqueue_style('studiofy-gallery-admin-css', STUDIOFY_URL . 'assets/css/gallery-admin.css', [], studiofy_get_asset_version('assets/css/gallery-admin.css'));
        }

        wp_enqueue_script('studiofy-admin-js', STUDIOFY_URL . 'assets/js/admin.js', ['jquery', 'jquery-ui-sortable', 'wp-color-picker', 'wp-api-fetch'], studiofy_get_asset_version('assets/js/admin.js'), true);

        if (strpos($hook, 'studiofy-galleries') !== false) {
            wp_enqueue_script('studiofy-gallery-admin-js', STUDIOFY_URL . 'assets/js/gallery-admin.js', ['jquery', 'wp-api-fetch'], studiofy_get_asset_version('assets/js/gallery-admin.js'), true);
            wp_localize_script('studiofy-gallery-admin-js', 'studiofyGallerySettings', [
                'root' => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('wp_rest'),
                'upload_nonce' => wp_create_nonce('studiofy_upload_chunk'),
                'max_upload_size' => wp_max_upload_size(),
                'ajax_url' => admin_url('admin-ajax.php')
            ]);
        }
    }

    public function render_footer_version($text): string {
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'studiofy') !== false) {
            return 'Studiofy CRM <b>v' . esc_html(STUDIOFY_VERSION) . '</b>';
        }
        return (string)$text;
    }
}
