<?php
/**
 * Settings Controller
 * @package Studiofy\Admin
 * @version 2.3.1
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
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
            echo '<div class="notice notice-success is-dismissible"><p><strong>Settings Saved.</strong></p></div>';
        }

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
        
        // --- Section 1: Business Identity ---
        add_settings_section('studiofy_branding_section', 'Business Identity', null, 'studiofy-settings');
        add_settings_field('business_name', 'Business Name', [$this, 'field_text'], 'studiofy-settings', 'studiofy_branding_section', ['key' => 'business_name', 'label' => 'Business Name']);
        add_settings_field('photo_types', 'Type(s) of Photography', [$this, 'field_text'], 'studiofy-settings', 'studiofy_branding_section', ['key' => 'photo_types', 'label' => 'Photography Types']);
        add_settings_field('business_logo', 'Logo', [$this, 'field_logo'], 'studiofy-settings', 'studiofy_branding_section');
        add_settings_field('google_maps_key', 'Google Maps API Key', [$this, 'field_text'], 'studiofy-settings', 'studiofy_branding_section', ['key' => 'google_maps_key', 'label' => 'Google Maps API Key']);

        // --- Section 2: Payment Configuration (NEW) ---
        add_settings_section('studiofy_payment_section', 'Payment Configuration', [$this, 'render_payment_intro'], 'studiofy-settings');
        
        // Square
        add_settings_field('square_heading', '<strong>Square Payments</strong>', [$this, 'field_separator'], 'studiofy-settings', 'studiofy_payment_section');
        add_settings_field('square_env', 'Environment', [$this, 'render_env_field'], 'studiofy-settings', 'studiofy_payment_section', ['key' => 'square_env']);
        add_settings_field('square_app_id', 'Application ID', [$this, 'field_text'], 'studiofy-settings', 'studiofy_payment_section', ['key' => 'square_app_id', 'label' => 'sq0idp-...']);
        add_settings_field('square_access_token', 'Access Token', [$this, 'field_password'], 'studiofy-settings', 'studiofy_payment_section', ['key' => 'square_access_token', 'label' => 'EAAA...']);
        add_settings_field('square_location_id', 'Location ID', [$this, 'field_text'], 'studiofy-settings', 'studiofy_payment_section', ['key' => 'square_location_id', 'label' => 'Location ID']);

        // Stripe
        add_settings_field('stripe_heading', '<strong>Stripe Payments</strong>', [$this, 'field_separator'], 'studiofy-settings', 'studiofy_payment_section');
        add_settings_field('stripe_env', 'Environment', [$this, 'render_env_field'], 'studiofy-settings', 'studiofy_payment_section', ['key' => 'stripe_env']);
        add_settings_field('stripe_pub_key', 'Publishable Key', [$this, 'field_text'], 'studiofy-settings', 'studiofy_payment_section', ['key' => 'stripe_pub_key', 'label' => 'pk_test_...']);
        add_settings_field('stripe_secret_key', 'Secret Key', [$this, 'field_password'], 'studiofy-settings', 'studiofy_payment_section', ['key' => 'stripe_secret_key', 'label' => 'sk_test_...']);

        // PayPal
        add_settings_field('paypal_heading', '<strong>PayPal REST API</strong>', [$this, 'field_separator'], 'studiofy-settings', 'studiofy_payment_section');
        add_settings_field('paypal_env', 'Environment', [$this, 'render_env_field'], 'studiofy-settings', 'studiofy_payment_section', ['key' => 'paypal_env']);
        add_settings_field('paypal_client_id', 'Client ID', [$this, 'field_text'], 'studiofy-settings', 'studiofy_payment_section', ['key' => 'paypal_client_id', 'label' => 'Client ID']);
        add_settings_field('paypal_secret', 'Secret Key', [$this, 'field_password'], 'studiofy-settings', 'studiofy_payment_section', ['key' => 'paypal_secret', 'label' => 'Secret']);

        // --- Section 3: Social Media ---
        add_settings_section('studiofy_social_section', 'Social Media', [$this, 'render_social_table'], 'studiofy-settings');
    }

    public function render_page(): void {
        echo '<div class="wrap"><h1>Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields($this->optionGroup);
        do_settings_sections('studiofy-settings');
        submit_button('Save Settings');
        echo '</form>';

        echo '<hr>';
        echo '<h2>Demo Data Import</h2>';
        $this->render_demo_section();
        echo '</div>';
    }

    // Helper to prevent nulls and allow array access
    private function get_safe_option($key, $default = []) {
        $opt = get_option($key, $default);
        $opt = json_decode(json_encode($opt), true); 
        if (!is_array($opt)) $opt = [];
        array_walk_recursive($opt, function(&$item){
            if(is_null($item)) $item = '';
        });
        return $opt;
    }

    public function render_payment_intro(): void {
        echo '<p>Configure your payment gateways below. Ensure you select the correct environment (Sandbox vs Production) for testing.</p>';
    }

    public function field_separator(): void {
        echo '<hr style="border-top:1px solid #ddd; border-bottom:none; margin:10px 0;">';
    }

    public function field_text(array $args): void {
        $options = $this->get_safe_option('studiofy_branding', []);
        $val = isset($options[$args['key']]) ? (string)$options[$args['key']] : '';
        $id = 'studiofy_' . $args['key'];
        
        echo '<label for="' . esc_attr($id) . '" class="screen-reader-text">' . esc_html($args['label']) . '</label>';
        echo '<input type="text" id="' . esc_attr($id) . '" name="studiofy_branding[' . esc_attr($args['key']) . ']" value="' . esc_attr($val) . '" class="regular-text" title="' . esc_attr($args['label']) . '" placeholder="' . esc_attr($args['label']) . '">';
    }

    public function field_password(array $args): void {
        $options = $this->get_safe_option('studiofy_branding', []);
        $val = isset($options[$args['key']]) ? (string)$options[$args['key']] : '';
        $id = 'studiofy_' . $args['key'];
        
        echo '<label for="' . esc_attr($id) . '" class="screen-reader-text">' . esc_html($args['label']) . '</label>';
        echo '<input type="password" id="' . esc_attr($id) . '" name="studiofy_branding[' . esc_attr($args['key']) . ']" value="' . esc_attr($val) . '" class="regular-text" title="' . esc_attr($args['label']) . '" placeholder="' . esc_attr($args['label']) . '">';
    }

    public function render_env_field(array $args): void {
         $options = $this->get_safe_option('studiofy_branding', []);
         $key = $args['key'];
         $env = isset($options[$key]) ? (string)$options[$key] : 'sandbox';
         $id = 'studiofy_' . $key;
         
         echo '<select id="' . esc_attr($id) . '" name="studiofy_branding[' . esc_attr($key) . ']">
            <option value="sandbox" '.selected($env,'sandbox',false).'>Sandbox / Test</option>
            <option value="production" '.selected($env,'production',false).'>Production / Live</option>
         </select>';
    }

    public function field_logo(): void {
        $options = $this->get_safe_option('studiofy_branding', []);
        $logo = isset($options['business_logo']) ? (string)$options['business_logo'] : '';
        echo '<div class="studiofy-media-uploader">';
        echo '<input type="text" id="studiofy_business_logo" name="studiofy_branding[business_logo]" value="' . esc_attr($logo) . '" class="regular-text" placeholder="Image URL">';
        echo '<button type="button" class="button studiofy-upload-btn" data-target="#studiofy_business_logo">Select Logo</button></div>';
        if ($logo) echo '<div style="margin-top:10px;"><img src="' . esc_url($logo) . '" style="max-height: 50px; border: 1px solid #ccc; padding: 2px;"></div>';
    }

    public function render_social_table(): void {
        $options = $this->get_safe_option('studiofy_branding', []);
        $socials = isset($options['social_media']) && is_array($options['social_media']) ? $options['social_media'] : [['network' => 'Instagram', 'url' => '']];
        echo '<table class="wp-list-table widefat fixed striped table-view-list">';
        echo '<thead><tr><th>Network Name</th><th>URL</th><th>Actions</th></tr></thead><tbody id="studiofy-social-tbody">';
        foreach ($socials as $index => $row) {
            $net = isset($row['network']) ? (string)$row['network'] : '';
            $url = isset($row['url']) ? (string)$row['url'] : '';
            echo "<tr>
                <td><input type='text' name='studiofy_branding[social_media][$index][network]' value='".esc_attr($net)."' class='regular-text' placeholder='Network'></td>
                <td><input type='url' name='studiofy_branding[social_media][$index][url]' value='".esc_attr($url)."' class='regular-text' placeholder='https://...'></td>
                <td><button type='button' class='button button-small delete-row'>Delete</button></td>
            </tr>";
        }
        echo '</tbody></table><p><button type="button" class="button" id="add-social-row">Add New Row</button></p>';
        echo '<script>jQuery(document).ready(function($){$("#add-social-row").click(function(){var idx=$("#studiofy-social-tbody tr").length;$("#studiofy-social-tbody").append(`<tr><td><input type="text" name="studiofy_branding[social_media][${idx}][network]" class="regular-text"></td><td><input type="url" name="studiofy_branding[social_media][${idx}][url]" class="regular-text"></td><td><button type="button" class="button button-small delete-row">Delete</button></td></tr>`)});$(document).on("click",".delete-row",function(){$(this).closest("tr").remove()})});</script>';
    }

    public function render_demo_section(): void {
        $has_demo = get_option('studiofy_demo_data_ids');
        ?>
        <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; max-width: 600px; border-radius: 4px; margin-top: 10px;">
            <p class="description">Upload the <code>Studiofy_Demo_data.xml</code> file to populate your CRM with test data.</p>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="studiofy_import_demo">
                <?php wp_nonce_field('import_demo', 'studiofy_nonce'); ?>
                <label for="demo_xml_file" style="font-weight: 600; display: block; margin-bottom: 5px;">Select XML File:</label>
                <input type="file" name="demo_xml_file" id="demo_xml_file" accept=".xml" required>
                <p class="submit" style="padding:0; margin-top:10px;">
                    <button type="submit" class="button button-secondary" <?php echo $has_demo ? 'disabled' : ''; ?>>
                        <?php echo $has_demo ? 'Demo Data Imported' : 'Upload & Import Data'; ?>
                    </button>
                </p>
            </form>
            <?php if ($has_demo): ?>
            <hr style="margin: 20px 0;">
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" onsubmit="return confirm('Delete demo data?');">
                <input type="hidden" name="action" value="studiofy_delete_demo">
                <?php wp_nonce_field('delete_demo', 'studiofy_nonce'); ?>
                <button type="submit" class="button button-link-delete" style="color: #d63638;">Delete Imported Demo Data</button>
            </form>
            <?php endif; ?>
        </div>
        <?php
    }
}
