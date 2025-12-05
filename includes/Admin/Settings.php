<?php
/**
 * Settings Controller
 * @package Studiofy\Admin
 * @version 2.0.7
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
        add_settings_section('studiofy_social_section', 'Social Media', [$this, 'render_social_table'], 'studiofy-settings');
    }

    public function render_page(): void {
        echo '<div class="wrap"><h1>Settings</h1><form method="post" action="options.php">';
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

    public function field_logo(): void {
        $options = get_option('studiofy_branding');
        $logo = $options['business_logo'] ?? '';
        echo '<div class="studiofy-media-uploader"><input type="text" name="studiofy_branding[business_logo]" id="studiofy_business_logo" value="' . esc_attr($logo) . '" class="regular-text"><button type="button" class="button studiofy-upload-btn" data-target="#studiofy_business_logo">Select Logo</button></div>';
    }

    public function render_social_table(): void {
        $options = get_option('studiofy_branding');
        $socials = $options['social_media'] ?? [['network' => 'Instagram', 'url' => '']];
        echo '<table class="wp-list-table widefat fixed striped table-view-list"><thead><tr><th>Network Name</th><th>URL</th><th>Actions</th></tr></thead><tbody id="studiofy-social-tbody">';
        foreach ($socials as $index => $row) {
            echo "<tr><td><input type='text' name='studiofy_branding[social_media][$index][network]' value='".esc_attr($row['network'])."' class='regular-text'></td><td><input type='url' name='studiofy_branding[social_media][$index][url]' value='".esc_attr($row['url'])."' class='regular-text'></td><td><button type='button' class='button button-small delete-row'>Delete</button></td></tr>";
        }
        echo '</tbody></table><p><button type="button" class="button" id="add-social-row">Add New Row</button></p>';
        echo '<script>jQuery(document).ready(function($){$("#add-social-row").click(function(){var idx=$("#studiofy-social-tbody tr").length;$("#studiofy-social-tbody").append(`<tr><td><input type="text" name="studiofy_branding[social_media][${idx}][network]" class="regular-text"></td><td><input type="url" name="studiofy_branding[social_media][${idx}][url]" class="regular-text"></td><td><button type="button" class="button button-small delete-row">Delete</button></td></tr>`)});$(document).on("click",".delete-row",function(){$(this).closest("tr").remove()})});</script>';
    }
}
