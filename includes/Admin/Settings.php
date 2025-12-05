<?php
/**
 * Settings
 * @package Studiofy\Admin
 * @version 2.0.1
 */
declare(strict_types=1);
namespace Studiofy\Admin;
use Studiofy\Utils\TableHelper;

class Settings {
    use TableHelper;
    private string $optionGroup = 'studiofy_branding_settings';
    public function init(): void { add_action('admin_init', [$this, 'register_settings']); }
    public function register_settings(): void {
        register_setting($this->optionGroup, 'studiofy_branding');
        // Add sections for logo, colors, and square_env
        add_settings_field('square_env', 'Square Environment', [$this, 'render_env_field'], 'studiofy-settings', 'studiofy_branding_section');
    }
    public function render_env_field(): void {
         $options = get_option('studiofy_branding');
         $env = $options['square_env'] ?? 'sandbox';
         echo '<select name="studiofy_branding[square_env]"><option value="sandbox" '.selected($env,'sandbox',false).'>Sandbox</option><option value="production" '.selected($env,'production',false).'>Production</option></select>';
    }
    public function render_page(): void { /* ... HTML ... */ }
}
