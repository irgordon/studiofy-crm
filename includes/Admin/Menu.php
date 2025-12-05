<?php
declare(strict_types=1);
namespace Studiofy\Admin;
use function Studiofy\studiofy_get_asset_version;

class Menu {
    private Settings $settings;
    private DashboardController $dashboardController;
    private ProjectController $projectController;
    private ContractController $contractController;
    private InvoiceController $invoiceController;
    private ClientController $clientController;
    private BookingController $bookingController;

    public function __construct() {
        $this->settings = new Settings();
        $this->dashboardController = new DashboardController();
        $this->projectController = new ProjectController();
        $this->contractController = new ContractController();
        $this->invoiceController = new InvoiceController();
        $this->clientController = new ClientController();
        $this->bookingController = new BookingController();
    }

    public function init(): void {
        add_action('admin_menu', [$this, 'register_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
        
        $this->settings->init();
        $this->projectController->init();
        $this->contractController->init();
        $this->invoiceController->init();
        $this->clientController->init();
        $this->bookingController->init();
        
        add_filter('admin_footer_text', [$this, 'render_footer_version']);
    }

    public function enqueue_styles($hook): void {
        if (strpos($hook, 'studiofy') === false) return;
        wp_enqueue_style('dashicons');
        wp_enqueue_style('studiofy-admin-css', STUDIOFY_URL . 'assets/css/admin.css', [], studiofy_get_asset_version('assets/css/admin.css'));
        wp_enqueue_script('studiofy-admin-js', STUDIOFY_URL . 'assets/js/admin.js', ['jquery', 'jquery-ui-sortable'], studiofy_get_asset_version('assets/js/admin.js'), true);
    }

    public function register_menu_pages(): void {
        add_menu_page('Studiofy CRM', 'Studiofy CRM', 'manage_options', 'studiofy-dashboard', [$this->dashboardController, 'render_page'], 'dashicons-camera', 6);
        add_submenu_page('studiofy-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'studiofy-dashboard', [$this->dashboardController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Clients', 'Clients', 'manage_options', 'studiofy-clients', [$this->clientController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Projects', 'Projects', 'manage_options', 'studiofy-projects', [$this->projectController, 'render_kanban_board']);
        add_submenu_page('studiofy-dashboard', 'Contracts', 'Contracts', 'manage_options', 'studiofy-contracts', [$this->contractController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Invoices', 'Invoices', 'manage_options', 'studiofy-invoices', [$this->invoiceController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Appointments', 'Appointments', 'manage_options', 'studiofy-appointments', [$this->bookingController, 'render_page']);
        add_submenu_page('studiofy-dashboard', 'Galleries', 'Galleries', 'manage_options', 'studiofy-galleries', [new GalleryController(), 'render_page']);
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
