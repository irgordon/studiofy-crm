<?php
/**
 * Settings Controller
 * @package Studiofy\Admin
 * @version 2.2.5
 */

declare(strict_types=1);

namespace Studiofy\Admin;

use Studiofy\Utils\TableHelper;

class Settings {
    use TableHelper;

    private string $optionGroup = 'studiofy_branding_settings';

    public function init(): void {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_notices', [$this, 'display_notices']);
    }

    public function display_notices(): void {
        // Native Settings Saved Notice
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
            echo '<div class="notice notice-success is-dismissible"><p><strong>Settings Saved.</strong></p></div>';
        }

        // Demo Data Notices
        if (isset($_GET['msg'])) {
            $msg = '';
            $type = 'success';
            switch($_GET['msg']) {
                case 'demo_imported': $msg = 'Demo data imported successfully.'; break;
                case 'demo_deleted': $msg = 'Demo data deleted successfully.'; break;
                case 'upload_error': 
                    $msg = 'Failed to upload file. Please upload a valid XML file.'; 
                    $type = 'error';
                    break;
                case 'xml_error': 
                    $msg = 'Invalid XML structure. Please check the file.'; 
                    $type = 'error';
                    break;
            }
            if($msg) echo "<div class='notice notice-$type is-dismissible'><p>$msg</p></div>";
        }
    }

    public function register_settings(): void {
        register_setting($this->optionGroup, 'studiofy_branding');
        
        add_settings_section('studiofy_branding_section', 'Business Identity', null, 'studiofy-settings');
        add_settings_field('business_name', 'Business Name', [$this, 'field_text'], 'studiofy-settings', 'studiofy_branding_section', ['key' => 'business_name']);
        add_settings_field('photo_types', 'Type(s) of Photography', [$this, 'field_text'], 'studiofy-settings', 'studiofy_branding_section', ['key' => 'photo_types']);
        add_settings_field('business_logo', 'Logo', [$this, 'field_logo'], 'studiofy-settings', 'studiofy_branding_section');
        
        add_settings_field('square_token', 'Square Access Token', [$this, 'field_text'], 'studiofy-settings', 'studiofy_branding_section', ['key' => 'square_access_token']);
        add_settings_field('square_env', 'Square Environment', [$this, 'render_env_field'], 'studiofy-settings', 'studiofy_branding_section');

        add_settings_section('studiofy_social_section', 'Social Media', [$this, 'render_social_table'], 'studiofy-settings');
        
        // Demo Data Section
        add_settings_section('studiofy_demo_section', 'Demo Data Import', [$this, 'render_demo_section'], 'studiofy-settings');
    }

    public function render_page(): void {
        echo '<div class="wrap"><h1>Settings</h1>';
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
        if (!empty($val)) echo '<p class="description" style="color: #2271b1; margin-top: 5px;">Current set value: <strong>' . esc_html($val) . '</strong></p>';
        else echo '<p class="description"><em>No value set.</em></p>';
    }

    public function render_env_field(): void {
         $options = get_option('studiofy_branding');
         $env = $options['square_env'] ?? 'sandbox';
         echo '<select name="studiofy_branding[square_env]"><option value="sandbox" '.selected($env,'sandbox',false).'>Sandbox</option><option value="production" '.selected($env,'production',false).'>Production</option></select>';
         echo '<p class="description" style="color: #2271b1; margin-top: 5px;">Current set value: <strong>' . esc_html(ucfirst($env)) . '</strong></p>';
    }

    public function field_logo(): void {
        $options = get_option('studiofy_branding');
        $logo = $options['business_logo'] ?? '';
        echo '<div class="studiofy-media-uploader"><input type="text" name="studiofy_branding[business_logo]" id="studiofy_business_logo" value="' . esc_attr($logo) . '" class="regular-text"><button type="button" class="button studiofy-upload-btn" data-target="#studiofy_business_logo">Select Logo</button></div>';
        if ($logo) echo '<div style="margin-top:10px;"><img src="' . esc_url($logo) . '" style="max-height: 50px; border: 1px solid #ccc; padding: 2px;"></div>';
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

    /**
     * Renders Demo Data File Upload & Delete
     */
    public function render_demo_section(): void {
        $has_demo = get_option('studiofy_demo_data_ids');
        ?>
        <p class="description">Upload the official <code>Studiofy_Demo_data.xml</code> file to populate your CRM with test data.</p>
        
        <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; max-width: 600px; border-radius: 4px; margin-top: 10px;">
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data" style="margin-bottom: 20px;">
                <input type="hidden" name="action" value="studiofy_import_demo">
                <?php wp_nonce_field('import_demo', 'studiofy_nonce'); ?>
                
                <label for="demo_xml_file" style="font-weight: 600; display: block; margin-bottom: 5px;">Select XML File:</label>
                <input type="file" name="demo_xml_file" id="demo_xml_file" accept=".xml" required>
                
                <p class="submit" style="padding:0; margin-top:10px;">
                    <button type="submit" class="button button-secondary" <?php echo $has_demo ? 'disabled' : ''; ?>>
                        <?php echo $has_demo ? 'Demo Data Already Imported' : 'Upload & Import Data'; ?>
                    </button>
                </p>
            </form>

            <?php if ($has_demo): ?>
            <hr style="margin: 20px 0;">
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" onsubmit="return confirm('Are you sure you want to delete all demo data? This action cannot be undone.');">
                <input type="hidden" name="action" value="studiofy_delete_demo">
                <?php wp_nonce_field('delete_demo', 'studiofy_nonce'); ?>
                <button type="submit" class="button button-link-delete" style="color: #d63638;">
                    Delete Imported Demo Data
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php
    }
}
