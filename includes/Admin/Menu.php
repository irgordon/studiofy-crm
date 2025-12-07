<?php
/**
 * Admin Menu Controller
 * @package Studiofy\Admin
 * @version 2.2.41
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
        // FIX: Explicitly cast titles to strings to satisfy strip_tags()
        add_menu_page('Studiofy CRM', 'Studiofy CRM', 'manage_options', 'studiofy-dashboard', [$this->dashboardController, 'render_page'], 'dashicons-camera', 6);
        
        add_submenu_page('studiofy-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'studiofy-dashboard', [$this->dashboardController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Customers', 'Customers', 'manage_options', 'studiofy-customers', [$this->customerController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Projects', 'Projects', 'manage_options', 'studiofy-projects', [$this->projectController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Contracts', 'Contracts', 'manage_options', 'studiofy-contracts', [$this->contractController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Invoices', 'Invoices', 'manage_options', 'studiofy-invoices', [$this->invoiceController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Appointments', 'Appointments', 'manage_options', 'studiofy-appointments', [$this->bookingController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Galleries', 'Galleries', 'manage_options', 'studiofy-galleries', [$this->galleryController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Settings', 'Settings', 'manage_options', 'studiofy-settings', [$this->settings, 'render_page']);
        
        // FIX: The hidden Welcome page. Parent is null, but title MUST be a string.
        add_submenu_page(null, 'Welcome', 'Welcome', 'manage_options', 'studiofy-welcome', [$this, 'render_welcome_page']);
    }

    public function render_welcome_page(): void {
        ?>
        <div class="wrap studiofy-welcome-wrap">
            <div class="studiofy-welcome-panel">
                <div class="studiofy-logo" style="margin-bottom: 20px;">
                    <svg width="250" height="200" viewBox="0 0 500 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="lens_gradient_std" x1="200" y1="130" x2="300" y2="230" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#4f94d4"/> <stop offset="1" stop-color="#2271b1"/> 
                            </linearGradient>
                        </defs>
                        <g id="Camera_Icon">
                            <rect x="100" y="80" width="300" height="200" rx="20" fill="black"/>
                            <path d="M180 80 L210 40 H290 L320 80 H180 Z" fill="black"/>
                            <rect x="120" y="70" width="40" height="10" rx="2" fill="black"/>
                            <circle cx="250" cy="180" r="85" fill="white"/> <circle cx="250" cy="180" r="75" fill="black"/> <circle cx="250" cy="180" r="60" fill="url(#lens_gradient_std)"/>
                            <ellipse cx="270" cy="160" rx="20" ry="12" transform="rotate(-45 270 160)" fill="white" fill-opacity="0.4"/>
                            <circle cx="230" cy="200" r="5" fill="white" fill-opacity="0.2"/>
                            <rect x="115" y="100" width="15" height="160" rx="5" fill="#333333"/>
                        </g>
                        <g id="Typography">
                            <text x="250" y="340" font-family="Arial, Helvetica, sans-serif" font-size="60" text-anchor="middle" fill="black">
                                <tspan font-weight="900" letter-spacing="2">STUDIOFY</tspan> 
                                <tspan font-weight="400" letter-spacing="4"> CRM</tspan>
                            </text>
                        </g>
                    </svg>
                </div>

                <p class="about-text" style="font-size:16px;">Get started by setting up your environment. Would you like to import demo data to see how the system works?</p>
                <p style="color:#666;">This will create sample Customers, Projects, Invoices, Contracts, and Galleries.</p>
                
                <div class="studiofy-welcome-actions">
                    <a href="<?php echo esc_url(admin_url('admin-post.php?action=studiofy_internal_import&nonce='.wp_create_nonce('internal_import'))); ?>" class="button button-primary button-hero">Yes, Import Demo Data</a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=studiofy-dashboard')); ?>" class="button button-secondary button-hero">No, Skip to Dashboard</a>
                </div>
            </div>
        </div>
        <style>
            .studiofy-welcome-wrap { display: flex; justify-content: center; align-items: center; min-height: 80vh; background: #f0f0f1; }
            .studiofy-welcome-panel { background: #fff; padding: 50px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); text-align: center; max-width: 600px; width: 100%; }
            .studiofy-welcome-actions { margin-top: 40px; display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; }
            .studiofy-logo svg { max-width: 100%; height: auto; }
        </style>
        <?php
    }

    public function enqueue_styles($hook): void {
        // FIX: Check if hook is null first to prevent strpos error
        if (!$hook) return;
        
        // FIX: Cast to string explicitly
        $hook_str = (string) $hook;
        
        if (strpos($hook_str, 'studiofy') === false) return;

        wp_enqueue_style('dashicons');
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_media();

        wp_enqueue_style('studiofy-admin-css', STUDIOFY_URL . 'assets/css/admin.css', [], studiofy_get_asset_version('assets/css/admin.css'));

        if (strpos($hook_str, 'studiofy-galleries') !== false) {
            wp_enqueue_style('studiofy-gallery-admin-css', STUDIOFY_URL . 'assets/css/gallery-admin.css', [], studiofy_get_asset_version('assets/css/gallery-admin.css'));
        }

        wp_enqueue_script('studiofy-admin-js', STUDIOFY_URL . 'assets/js/admin.js', ['jquery', 'jquery-ui-sortable', 'wp-color-picker', 'wp-api-fetch'], studiofy_get_asset_version('assets/js/admin.js'), true);

        if (strpos($hook_str, 'studiofy-galleries') !== false) {
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
        // FIX: Check screen object first
        if ($screen && isset($screen->id) && strpos((string)$screen->id, 'studiofy') !== false) {
            return 'Studiofy CRM <b>v' . esc_html(STUDIOFY_VERSION) . '</b>';
        }
        return (string) $text;
    }
}
