<?php
/**
 * Booking Widget
 * @package Studiofy\Elementor\Widgets
 * @version 2.0.4
 */

declare(strict_types=1);

namespace Studiofy\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class BookingWidget extends Widget_Base {

    public function get_name(): string { return 'studiofy_scheduler'; }
    public function get_title(): string { return esc_html__('Studiofy Scheduler', 'studiofy'); }
    public function get_icon(): string { return 'eicon-calendar'; }
    public function get_categories(): array { return ['studiofy-category']; }

    protected function register_controls(): void {
        $this->start_controls_section('content_section', ['label' => 'Settings', 'tab' => Controls_Manager::TAB_CONTENT]);

        $this->add_control('service_name', ['label' => 'Service Name', 'type' => Controls_Manager::TEXT, 'default' => 'Portrait Session']);
        
        $this->add_control('available_days', [
            'label' => 'Available Days',
            'type' => Controls_Manager::SELECT2,
            'options' => ['1' => 'Monday', '2' => 'Tuesday', '3' => 'Wednesday', '4' => 'Thursday', '5' => 'Friday', '6' => 'Saturday', '0' => 'Sunday'],
            'multiple' => true,
            'default' => ['1', '2', '3', '4', '5']
        ]);

        $this->end_controls_section();

        $this->start_controls_section('style_section', ['label' => 'Calendar Style', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('calendar_color', ['label' => 'Accent Color', 'type' => Controls_Manager::COLOR, 'default' => '#2271b1', 'selectors' => ['{{WRAPPER}} .studiofy-calendar-day.selected' => 'background-color: {{VALUE}}', '{{WRAPPER}} .studiofy-time-slot:hover' => 'border-color: {{VALUE}}; color: {{VALUE}}', '{{WRAPPER}} .studiofy-time-slot.selected' => 'background-color: {{VALUE}}; border-color: {{VALUE}}; color: #fff']]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $days_allowed = json_encode($settings['available_days']);

        echo '<div class="studiofy-scheduler" data-service="' . esc_attr($settings['service_name']) . '" data-days="' . esc_attr($days_allowed) . '">';
        
        echo '<div class="studiofy-scheduler-header">';
        echo '<h3>Book: ' . esc_html($settings['service_name']) . '</h3>';
        echo '<div class="studiofy-month-nav">';
        echo '<button type="button" class="prev-month">&laquo;</button>';
        echo '<span class="current-month-label"></span>';
        echo '<button type="button" class="next-month">&raquo;</button>';
        echo '</div></div>';

        echo '<div class="studiofy-calendar-grid"></div>';

        echo '<div class="studiofy-time-slots hidden">';
        echo '<h4>Select Time</h4>';
        echo '<div class="slots-container"></div>';
        echo '</div>';

        echo '<form class="studiofy-booking-form hidden">';
        echo '<input type="text" name="name" placeholder="Your Name" required>';
        echo '<input type="email" name="email" placeholder="Your Email" required>';
        echo '<button type="submit">Confirm Booking</button>';
        echo '</form>';

        echo '</div>';
    }
}
