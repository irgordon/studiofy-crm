<?php
/**
 * Admin Menu Controller
 * @package Studiofy\Admin
 * @version 2.3.11
 */

declare(strict_types=1);

namespace Studiofy\Admin;

use function Studiofy\studiofy_get_asset_version;

class Menu {

    private Settings $settings;
    private DashboardController $dashboardController;
    private ProjectController $projectController;
    private BillingController $billingController;
    private CustomerController $customerController;
    private BookingController $bookingController;
    private GalleryController $galleryController;

    public function __construct() {
        $this->settings = new Settings();
        $this->dashboardController = new DashboardController();
        $this->projectController = new ProjectController();
        $this->billingController = new BillingController();
        $this->customerController = new CustomerController();
        $this->bookingController = new BookingController();
        $this->galleryController = new GalleryController();
    }

    public function init(): void {
        add_action('admin_menu', [$this, 'register_menu_pages']);
        add_action('admin_head', [$this, 'hide_welcome_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('admin_init', [$this, 'activation_redirect']);
        add_action('admin_post_studiofy_save_welcome', [$this, 'handle_save_welcome']); // New Handler
        
        $this->settings->init();
        $this->projectController->init();
        $this->billingController->init();
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
        add_menu_page('Studiofy CRM', 'Studiofy CRM', 'manage_options', 'studiofy-dashboard', [$this->dashboardController, 'render_page'], 'dashicons-camera', 6);
        add_submenu_page('studiofy-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'studiofy-dashboard', [$this->dashboardController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Customers', 'Customers', 'manage_options', 'studiofy-customers', [$this->customerController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Projects', 'Projects', 'manage_options', 'studiofy-projects', [$this->projectController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Billing', 'Billing', 'manage_options', 'studiofy-billing', [$this->billingController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Appointments', 'Appointments', 'manage_options', 'studiofy-booking', [$this->bookingController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Galleries', 'Galleries', 'manage_options', 'studiofy-galleries', [$this->galleryController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Settings', 'Settings', 'manage_options', 'studiofy-settings', [$this->settings, 'render_page']);
        
        add_submenu_page('index.php', 'Welcome', 'Welcome', 'manage_options', 'studiofy-welcome', [$this, 'render_welcome_page']);
    }

    public function hide_welcome_menu(): void {
        echo '<style>a[href="admin.php?page=studiofy-welcome"], #menu-dashboard a[href="admin.php?page=studiofy-welcome"] { display: none !important; }</style>';
    }

    public function handle_save_welcome(): void {
        check_admin_referer('studiofy_welcome_save', 'studiofy_nonce');
        
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $branding = get_option('studiofy_branding', []);
        
        $branding['business_name'] = sanitize_text_field($_POST['business_name']);
        $branding['business_logo'] = esc_url_raw($_POST['business_logo']);
        $branding['business_address'] = sanitize_textarea_field($_POST['business_address']);
        $branding['business_phone'] = sanitize_text_field($_POST['business_phone']);

        update_option('studiofy_branding', $branding);

        // Redirect back to welcome or dashboard? User might want to import demo data next.
        wp_redirect(admin_url('admin.php?page=studiofy-welcome&saved=1'));
        exit;
    }

    public function render_welcome_page(): void {
        $branding = get_option('studiofy_branding', []);
        $name = $branding['business_name'] ?? '';
        $logo = $branding['business_logo'] ?? '';
        $addr = $branding['business_address'] ?? '';
        $phone = $branding['business_phone'] ?? '';
        ?>
        <div class="wrap studiofy-welcome-wrap">
            <div class="studiofy-welcome-panel">
                <div class="studiofy-logo">
                    <svg width="150" height="120" viewBox="0 0 500 400" fill="none" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="lg" x1="200" y1="130" x2="300" y2="230" gradientUnits="userSpaceOnUse"><stop stop-color="#4f94d4"/><stop offset="1" stop-color="#2271b1"/></linearGradient></defs><g><rect x="100" y="80" width="300" height="200" rx="20" fill="black"/><path d="M180 80 L210 40 H290 L320 80 H180 Z" fill="black"/><rect x="120" y="70" width="40" height="10" rx="2" fill="black"/><circle cx="250" cy="180" r="85" fill="white"/><circle cx="250" cy="180" r="75" fill="black"/><circle cx="250" cy="180" r="60" fill="url(#lg)"/><ellipse cx="270" cy="160" rx="20" ry="12" transform="rotate(-45 270 160)" fill="white" fill-opacity="0.4"/><circle cx="230" cy="200" r="5" fill="white" fill-opacity="0.2"/><rect x="115" y="100" width="15" height="160" rx="5" fill="#333333"/></g><g><text x="250" y="340" font-family="Arial, Helvetica, sans-serif" font-size="60" text-anchor="middle" fill="black"><tspan font-weight="900" letter-spacing="2">STUDIOFY</tspan> <tspan font-weight="400" letter-spacing="4"> CRM</tspan></text></g></svg>
                </div>

                <h1>Welcome to Studiofy CRM</h1>
                <p class="about-text">Let's set up your studio details. This information will be used throughout the site for your Billing headers, Contract details, and Contact information.</p>
                
                <?php if(isset($_GET['saved'])): ?>
                    <div class="notice notice-success inline"><p>Settings Saved! You can now import demo data or go to dashboard.</p></div>
                <?php endif; ?>

                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="studiofy-welcome-form">
                    <input type="hidden" name="action" value="studiofy_save_welcome">
                    <?php wp_nonce_field('studiofy_welcome_save', 'studiofy_nonce'); ?>
                    
                    <div class="form-group">
                        <label>Business Name</label>
                        <input type="text" name="business_name" value="<?php echo esc_attr($name); ?>" placeholder="e.g. Acme Photography" class="widefat" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Business Address</label>
                        <textarea name="business_address" rows="2" placeholder="123 Main St, City, State, Zip" class="widefat"><?php echo esc_textarea($addr); ?></textarea>
                    </div>

                    <div class="form-group-row">
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="business_phone" value="<?php echo esc_attr($phone); ?>" placeholder="(555) 123-4567" class="widefat">
                        </div>
                        <div class="form-group">
                            <label>Logo URL</label>
                            <input type="text" name="business_logo" value="<?php echo esc_attr($logo); ?>" placeholder="https://..." class="widefat">
                        </div>
                    </div>

                    <p class="submit">
                        <button type="submit" class="button button-primary button-hero">Save Business Info</button>
                    </p>
                </form>

                <hr style="margin: 30px 0; border:0; border-top:1px solid #eee;">

                <h3>Next Steps</h3>
                <div class="studiofy-welcome-actions">
                    <a href="<?php echo esc_url(admin_url('admin-post.php?action=studiofy_internal_import&nonce='.wp_create_nonce('internal_import'))); ?>" class="button button-secondary button-large">Import Demo Data</a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=studiofy-dashboard')); ?>" class="button button-link">Skip to Dashboard &rarr;</a>
                </div>
            </div>
        </div>
        <style>
            .studiofy-welcome-wrap { display: flex; justify-content: center; align-items: flex-start; min-height: 80vh; background: #f0f0f1; padding-top: 40px; }
            .studiofy-welcome-panel { background: #fff; padding: 50px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); text-align: center; max-width: 600px; width: 100%; }
            .studiofy-welcome-form { text-align: left; margin-top: 30px; background: #f9f9f9; padding: 20px; border-radius: 4px; border: 1px solid #eee; }
            .form-group { margin-bottom: 15px; }
            .form-group label { display: block; font-weight: 600; margin-bottom: 5px; color: #444; }
            .form-group-row { display: flex; gap: 15px; }
            .form-group-row .form-group { flex: 1; }
            .studiofy-logo svg { max-width: 150px; height: auto; }
            .about-text { font-size: 15px; line-height: 1.5; color: #555; }
            .studiofy-welcome-actions { display: flex; gap: 15px; justify-content: center; align-items: center; }
        </style>
        <?php
    }

    public function enqueue_styles($hook): void {
        if (!$hook) return;
        $hook_str = (string) $hook;
        
        if (strpos($hook_str, 'studiofy') === false) return;

        wp_enqueue_style('dashicons');
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_media();
        wp_enqueue_editor(); 

        wp_enqueue_style('studiofy-admin-css', STUDIOFY_URL . 'assets/css/admin.css', [], studiofy_get_asset_version('assets/css/admin.css'));

        if (strpos($hook_str, 'studiofy-billing') !== false) {
            wp_enqueue_style('studiofy-billing-css', STUDIOFY_URL . 'assets/css/billing.css', [], studiofy_get_asset_version('assets/css/billing.css'));
            wp_enqueue_script('studiofy-billing-js', STUDIOFY_URL . 'assets/js/billing.js', ['jquery'], studiofy_get_asset_version('assets/js/billing.js'), true);
        }

        if (strpos($hook_str, 'studiofy-galleries') !== false) {
            wp_enqueue_style('studiofy-gallery-admin-css', STUDIOFY_URL . 'assets/css/gallery-admin.css', [], studiofy_get_asset_version('assets/css/gallery-admin.css'));
            wp_enqueue_script('studiofy-gallery-admin-js', STUDIOFY_URL . 'assets/js/gallery-admin.js', ['jquery', 'wp-api-fetch'], studiofy_get_asset_version('assets/js/gallery-admin.js'), true);
            wp_localize_script('studiofy-gallery-admin-js', 'studiofyGallerySettings', [
                'root' => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('wp_rest'),
                'upload_nonce' => wp_create_nonce('studiofy_upload_chunk'),
                'max_upload_size' => wp_max_upload_size(),
                'ajax_url' => admin_url('admin-ajax.php')
            ]);
        }
        
        wp_enqueue_script('studiofy-admin-js', STUDIOFY_URL . 'assets/js/admin.js', ['jquery', 'jquery-ui-sortable', 'wp-color-picker', 'wp-api-fetch'], studiofy_get_asset_version('assets/js/admin.js'), true);
    }

    public function render_footer_version($text): string {
        $screen = get_current_screen();
        if ($screen && isset($screen->id) && strpos((string)$screen->id, 'studiofy') !== false) {
            return 'Studiofy CRM <b>v' . esc_html(STUDIOFY_VERSION) . '</b>';
        }
        return (string) $text;
    }
}
