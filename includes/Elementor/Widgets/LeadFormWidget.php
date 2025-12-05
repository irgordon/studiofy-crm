<?php
/**
 * Lead Form Widget
 * @package Studiofy\Elementor\Widgets
 * @version 2.0.1
 */
declare(strict_types=1);
namespace Studiofy\Elementor\Widgets;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
if (!defined('ABSPATH')) exit;

class LeadFormWidget extends Widget_Base {
    public function get_name(): string { return 'studiofy_lead_form'; }
    public function get_title(): string { return 'Studiofy Lead Form'; }
    public function get_icon(): string { return 'eicon-form-horizontal'; }
    public function get_categories(): array { return ['studiofy-category']; }

    protected function register_controls(): void {
        $this->start_controls_section('content_section', ['label' => 'Form Fields', 'tab' => Controls_Manager::TAB_CONTENT]);
        $repeater = new Repeater();
        $repeater->add_control('field_label', ['label' => 'Label', 'type' => Controls_Manager::TEXT, 'default' => 'Field']);
        $repeater->add_control('field_type', ['label' => 'Type', 'type' => Controls_Manager::SELECT, 'options' => ['text'=>'Text','email'=>'Email','textarea'=>'TextArea'], 'default' => 'text']);
        $repeater->add_control('db_mapping', ['label' => 'Map to', 'type' => Controls_Manager::SELECT, 'options' => ['first_name'=>'First Name','email'=>'Email','phone'=>'Phone'], 'default' => 'first_name']);
        
        $this->add_control('form_fields', ['label' => 'Fields', 'type' => Controls_Manager::REPEATER, 'fields' => $repeater->get_controls()]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        echo '<form class="studiofy-lead-form elementor-form" method="post">';
        foreach ($settings['form_fields'] as $field) {
            echo '<label>'.esc_html($field['field_label']).'</label>';
            echo '<input type="'.esc_attr($field['field_type']).'" name="'.esc_attr($field['db_mapping']).'" class="elementor-field">';
        }
        echo '<button type="submit" class="elementor-button">Send</button></form>';
    }
}
