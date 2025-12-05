<?php
/**
 * Settings Page with CRUD Table
 * @package Studiofy\Admin
 * @version 2.0.1
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
        add_settings_field('photo_types', 'Type(s) of Photography', [$this, 'field_text'], 'studiofy-settings', 'studiofy_branding_section', ['key' => 'photo_types']);
        add_settings_field('business_logo', 'Logo', [$this, 'field_logo'], 'studiofy-settings', 'studiofy_branding_section');
        
        // Square API Settings
        add_settings_field('square_token', 'Square Access Token', [$this, 'field_text'], 'studiofy-settings', 'studiofy_branding_section', ['key' => 'square_access_token']);
        add_settings_field('square_env', 'Square Environment', [$this, 'render_env_field'], 'studiofy-settings', 'studiofy_branding_section');

        // Social Media Section
        add_settings_section('studiofy_social_section', 'Social Media', [$this, 'render_social_table'], 'studiofy-settings');
    }

    public function render_page(): void {
        if (isset($_GET['welcome'])) {
            echo '<div class="notice notice-info is-dismissible"><p><strong>Welcome to Studiofy CRM!</strong> Please configure your Business details below to get started.</p></div>';
        }
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Studiofy CRM Settings</h1>
            <hr class="wp-header-end">
            <form method="post" action="options.php">
                <?php
                settings_fields($this->optionGroup);
                do_settings_sections('studiofy-settings');
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
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

    public function render_social_table(): void {
        $options = get_option('studiofy_branding');
        $socials = $options['social_media'] ?? [['network' => 'Instagram', 'url' => '']];

        echo '<p>Manage your social media links below.</p>';
        echo '<table class="wp-list-table widefat fixed striped table-view-list">';
        echo '<thead><tr><th>Network Name</th><th>URL</th><th>Actions</th></tr></thead>';
        echo '<tbody id="studiofy-social-tbody">';

        foreach ($socials as $index => $row) {
            $name = esc_attr($row['network']);
            $url = esc_attr($row['url']);
            echo "<tr>
                <td><input type='text' name='studiofy_branding[social_media][$index][network]' value='$name' class='regular-text'></td>
                <td><input type='url' name='studiofy_branding[social_media][$index][url]' value='$url' class='regular-text' style='width:100%'></td>
                <td><button type='button' class='button button-small delete-row'>Delete</button></td>
            </tr>";
        }
        echo '</tbody></table>';
        echo '<p><button type="button" class="button" id="add-social-row">Add New Row</button></p>';
        
        ?>
        <script>
        jQuery(document).ready(function($){
            $('#add-social-row').click(function(){
                var idx = $('#studiofy-social-tbody tr').length;
                var row = `<tr><td><input type='text' name='studiofy_branding[social_media][${idx}][network]' class='regular-text'></td><td><input type='url' name='studiofy_branding[social_media][${idx}][url]' class='regular-text' style='width:100%'></td><td><button type='button' class='button button-small delete-row'>Delete</button></td></tr>`;
                $('#studiofy-social-tbody').append(row);
            });
            $(document).on('click', '.delete-row', function(){ $(this).closest('tr').remove(); });
        });
        </script>
        <?php
    }
}
