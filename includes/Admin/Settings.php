<?php
/**
 * Settings Controller
 * @package Studiofy\Admin
 * @version 2.0.4
 */

declare(strict_types=1);

namespace Studiofy\Admin;

use Studiofy\Utils\TableHelper;

class Settings {
    use TableHelper;

    private string $optionGroup = 'studiofy_branding_settings';

    public function init(): void {
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_settings(): void {
        register_setting($this->optionGroup, 'studiofy_branding');
        
        add_settings_section('studiofy_branding_section', 'Business Identity', null, 'studiofy-settings');
        add_settings_field('business_name', 'Business Name', [$this, 'field_text'], 'studiofy-settings', 'studiofy_branding_section', ['key' => 'business_name']);
        add_settings_field('business_logo', 'Logo', [$this, 'field_logo'], 'studiofy-settings', 'studiofy_branding_section');
        
        add_settings_field('square_token', 'Square Access Token', [$this, 'field_text'], 'studiofy-settings', 'studiofy_branding_section', ['key' => 'square_access_token']);
        add_settings_field('square_env', 'Square Environment', [$this, 'render_env_field'], 'studiofy-settings', 'studiofy_branding_section');
    }

    public function render_page(): void {
        echo '<div class="wrap studiofy-dark-theme"><h1>Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields($this->optionGroup);
        do_settings_sections('studiofy-settings');
        submit_button('Save Settings');
        echo '</form></div>';
    }

    public function field_text(array $args): void {
        $options = get_option('studiofy_branding');
        $val = $options[$args['key']] ?? '';
        echo '<input type="text" name="studiofy_branding[' . esc_attr($args['key']) . ']" value="' . esc_attr($val) . '" class="regular-text">';
    }

    public function render_env_field(): void {
         $options = get_option('studiofy_branding');
         $env = $options['square_env'] ?? 'sandbox';
         echo '<select name="studiofy_branding[square_env]"><option value="sandbox" '.selected($env,'sandbox',false).'>Sandbox</option><option value="production" '.selected($env,'production',false).'>Production</option></select>';
    }

    public function field_logo(): void {
        $options = get_option('studiofy_branding');
        $logo = $options['business_logo'] ?? '';
        echo '<div class="studiofy-media-uploader">';
        echo '<input type="text" name="studiofy_branding[business_logo]" id="studiofy_business_logo" value="' . esc_attr($logo) . '" class="regular-text">';
        echo '<button type="button" class="button studiofy-upload-btn" data-target="#studiofy_business_logo">Select Logo</button>';
        echo '</div>';
    }
}
