<?php
/**
 * Booking Widget
 * @package Studiofy\Elementor\Widgets
 * @version 2.0.1
 */
declare(strict_types=1);
namespace Studiofy\Elementor\Widgets;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
if (!defined('ABSPATH')) exit;

class BookingWidget extends Widget_Base {
    public function get_name(): string { return 'studiofy_scheduler'; }
    public function get_title(): string { return 'Studiofy Scheduler'; }
    public function get_icon(): string { return 'eicon-calendar'; }
    public function get_categories(): array { return ['studiofy-category']; }

    protected function register_controls(): void {
        $this->start_controls_section('content_section', ['label' => 'Settings', 'tab' => Controls_Manager::TAB_CONTENT]);
        $this->add_control('service_name', ['label' => 'Service', 'type' => Controls_Manager::TEXT, 'default' => 'Session']);
        $this->add_control('available_days', ['label' => 'Days', 'type' => Controls_Manager::SELECT2, 'multiple' => true, 'options' => ['1'=>'Mon','2'=>'Tue','3'=>'Wed','4'=>'Thu','5'=>'Fri'], 'default' => ['1','2','3','4','5']]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $days = json_encode($settings['available_days']);
        
        // Escaped Attributes
        echo '<div class="studiofy-scheduler" data-service="'.esc_attr($settings['service_name']).'" data-days="'.esc_attr($days).'">';
        echo '<div class="studiofy-calendar-grid"></div>';
        echo '<form class="studiofy-booking-form hidden"><input name="name" placeholder="Name"><input name="email" placeholder="Email"><button>Book</button></form>';
        echo '</div>';
    }
}
