<?php
/**
 * Lead Form Widget
 * @package Studiofy\Elementor\Widgets
 * @version 2.0.5
 */

declare(strict_types=1);

namespace Studiofy\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

if (!defined('ABSPATH')) exit;

class LeadFormWidget extends Widget_Base {

    public function get_name(): string { return 'studiofy_lead_form'; }
    public function get_title(): string { return esc_html__('Studiofy Lead Form', 'studiofy'); }
    public function get_icon(): string { return 'eicon-form-horizontal'; }
    public function get_categories(): array { return ['studiofy-category']; }

    protected function register_controls(): void {
        $this->start_controls_section('content_section', ['label' => 'Form Fields', 'tab' => Controls_Manager::TAB_CONTENT]);

        $repeater = new Repeater();
        $repeater->add_control('field_label', ['label' => 'Label', 'type' => Controls_Manager::TEXT, 'default' => 'Field Name']);
        $repeater->add_control('field_type', ['label' => 'Type', 'type' => Controls_Manager::SELECT, 'options' => ['text' => 'Text', 'email' => 'Email', 'textarea' => 'Text Area'], 'default' => 'text']);
        $repeater->add_control('db_mapping', ['label' => 'Map to CRM Field', 'type' => Controls_Manager::SELECT, 'options' => ['first_name' => 'First Name', 'last_name' => 'Last Name', 'email' => 'Email', 'phone' => 'Phone'], 'default' => 'first_name']);

        $this->add_control('form_fields', ['label' => 'Fields', 'type' => Controls_Manager::REPEATER, 'fields' => $repeater->get_controls(), 'default' => [['field_label' => 'First Name', 'field_type' => 'text', 'db_mapping' => 'first_name'], ['field_label' => 'Email', 'field_type' => 'email', 'db_mapping' => 'email']], 'title_field' => '{{{ field_label }}}']);
        $this->add_control('button_text', ['label' => 'Button Text', 'type' => Controls_Manager::TEXT, 'default' => 'Send Inquiry']);
        
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();

        echo '<form class="studiofy-lead-form elementor-form" method="post">';
        wp_nonce_field('studiofy_lead_submit', 'studiofy_nonce');

        foreach ($settings['form_fields'] as $field) {
            $type = $field['field_type'];
            $name = $field['db_mapping'];
            $label = $field['field_label'];

            echo '<div class="elementor-field-group elementor-column elementor-col-100">';
            echo '<label class="elementor-field-label">' . esc_html($label) . '</label>';

            if ($type === 'textarea') {
                echo '<textarea name="' . esc_attr($name) . '" class="elementor-field elementor-size-sm" rows="4"></textarea>';
            } else {
                echo '<input type="' . esc_attr($type) . '" name="' . esc_attr($name) . '" class="elementor-field elementor-size-sm">';
            }
            echo '</div>';
        }

        echo '<div class="elementor-field-group elementor-column elementor-col-100">';
        echo '<button type="submit" class="elementor-button elementor-size-sm">' . esc_html($settings['button_text']) . '</button>';
        echo '</div>';
        echo '</form>';
        
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.studiofy-lead-form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var btn = form.find('button');
                var data = {};
                
                form.find('input, textarea').each(function() {
                    data[$(this).attr('name')] = $(this).val();
                });

                btn.text('Sending...');

                $.ajax({
                    // Updated endpoint to /customers
                    url: '<?php echo esc_url_raw(rest_url("studiofy/v1/customers")); ?>',
                    method: 'POST',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce("wp_rest"); ?>');
                    },
                    data: JSON.stringify(data),
                    contentType: 'application/json',
                    success: function() {
                        btn.text('Sent!');
                        form[0].reset();
                    },
                    error: function() {
                        btn.text('Error. Try again.');
                    }
                });
            });
        });
        </script>
        <?php
    }
}
