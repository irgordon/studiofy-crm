<?php
/**
 * Gallery Widget
 * @package Studiofy\Elementor\Widgets
 * @version 2.0.1
 */
declare(strict_types=1);
namespace Studiofy\Elementor\Widgets;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
if (!defined('ABSPATH')) exit;

class GalleryWidget extends Widget_Base {
    public function get_name(): string { return 'studiofy_gallery'; }
    public function get_title(): string { return 'Studiofy Gallery'; }
    public function get_icon(): string { return 'eicon-gallery-grid'; }
    public function get_categories(): array { return ['studiofy-category']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => 'Gallery Settings', 'tab' => Controls_Manager::TAB_CONTENT]);
        $folders = get_terms(['taxonomy' => 'studiofy_folder', 'hide_empty' => false]);
        $options = [];
        if (!is_wp_error($folders)) { foreach ($folders as $term) $options[$term->term_id] = $term->name; }

        $this->add_control('folder_id', ['label' => 'Select Folder', 'type' => Controls_Manager::SELECT, 'options' => $options]);
        $this->add_control('mode', ['label' => 'Mode', 'type' => Controls_Manager::SELECT, 'options' => ['grid' => 'Grid', 'proofing' => 'Proofing'], 'default' => 'grid']);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $folder_id = $settings['folder_id'];
        if (!$folder_id) return;
        
        // Performance: Check Transient Cache
        $cache_key = 'studiofy_gallery_html_' . $folder_id . '_' . $settings['mode'];
        $cached_output = get_transient($cache_key);

        if (false !== $cached_output && !is_user_logged_in()) { // Only serve cache to non-admins
             echo $cached_output;
             echo "<script>window.studiofyGallery = window.studiofyGallery || {}; window.studiofyGallery.current_id = {$folder_id};</script>";
             return;
        }

        $args = ['post_type' => 'attachment', 'posts_per_page' => -1, 'tax_query' => [['taxonomy' => 'studiofy_folder', 'field' => 'term_id', 'terms' => $folder_id]]];
        $query = new \WP_Query($args);
        $watermarker = new \Studiofy\Media\Watermarker();
        
        ob_start();
        echo '<form id="studiofy-proofing-form" class="studiofy-gallery-wrapper"><div class="studiofy-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            $id = get_the_ID();
            $src = ($settings['mode'] === 'proofing') ? $watermarker->apply_watermark($id) : wp_get_attachment_image_url($id, 'medium_large');
            if(!$src) $src = wp_get_attachment_image_url($id, 'medium');
            
            // Escaped Output
            echo '<div class="studiofy-item"><img src="'.esc_url($src).'">';
            if ($settings['mode'] === 'proofing') echo '<input type="checkbox" name="selected_photos[]" value="'.$id.'">';
            echo '</div>';
        }
        echo '</div>';
        if ($settings['mode'] === 'proofing') echo '<button type="submit" class="elementor-button">Submit Selections</button>';
        echo '</form>';
        
        $output = ob_get_clean();
        
        // Save to Transient for 1 hour
        set_transient($cache_key, $output, 3600);
        
        echo $output;
        echo "<script>window.studiofyGallery = window.studiofyGallery || {}; window.studiofyGallery.current_id = {$folder_id};</script>";
        wp_reset_postdata();
    }
}
