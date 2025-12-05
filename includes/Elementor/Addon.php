<?php
declare(strict_types=1);
namespace Studiofy\Elementor;

class Addon {
    private static $_instance = null;
    public static function instance() {
        if (is_null(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }
    public function __construct() {
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
        add_action('elementor/elements/categories_registered', [$this, 'register_categories']);
        add_action('elementor/frontend/after_enqueue_styles', [$this, 'enqueue_styles']);
        add_action('elementor/frontend/after_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    public function register_categories($elements_manager) {
        $elements_manager->add_category('studiofy-category', ['title' => 'Studiofy CRM', 'icon' => 'fa fa-camera']);
    }
    public function register_widgets($widgets_manager) {
        require_once STUDIOFY_PATH . 'includes/Elementor/Widgets/GalleryWidget.php';
        require_once STUDIOFY_PATH . 'includes/Elementor/Widgets/LeadFormWidget.php';
        require_once STUDIOFY_PATH . 'includes/Elementor/Widgets/BookingWidget.php';
        
        $widgets_manager->register(new Widgets\GalleryWidget());
        $widgets_manager->register(new Widgets\LeadFormWidget());
        $widgets_manager->register(new Widgets\BookingWidget());
    }
    public function enqueue_styles() {
        wp_enqueue_style('studiofy-gallery-css', STUDIOFY_URL . 'assets/css/gallery.css', [], STUDIOFY_VERSION);
        wp_enqueue_style('studiofy-booking-css', STUDIOFY_URL . 'assets/css/booking.css', [], STUDIOFY_VERSION);
    }
    public function enqueue_scripts() {
        wp_enqueue_script('studiofy-gallery-js', STUDIOFY_URL . 'assets/js/gallery-view.js', ['jquery'], STUDIOFY_VERSION, true);
        wp_enqueue_script('studiofy-booking-js', STUDIOFY_URL . 'assets/js/booking-widget.js', ['jquery'], STUDIOFY_VERSION, true);
        
        wp_localize_script('studiofy-gallery-js', 'studiofyGallery', ['root' => esc_url_raw(rest_url()), 'nonce' => wp_create_nonce('wp_rest')]);
        // Note: booking-widget.js uses studiofySettings localized in Plugin or Menu logic, ensure it's loaded frontend or localize specifically here:
        wp_localize_script('studiofy-booking-js', 'studiofySettings', ['root' => esc_url_raw(rest_url()), 'nonce' => wp_create_nonce('wp_rest')]);
    }
}
