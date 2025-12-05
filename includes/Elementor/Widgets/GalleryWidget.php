<?php
/**
 * Studiofy Gallery Widget
 * Visual Proofing Gallery for Elementor.
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
    public function get_title(): string { return esc_html__('Studiofy Gallery', 'studiofy'); }
    public function get_icon(): string { return 'eicon-gallery-grid'; }
    public function get_categories(): array { return ['studiofy-category']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => esc_html__('Gallery Settings', 'studiofy'), 'tab' => Controls_Manager::TAB_CONTENT]);

        $folders = get_terms(['taxonomy' => 'studiofy_folder', 'hide_empty' => false]);
        $options = [];
        if (!is_wp_error($folders)) {
            foreach ($folders as $term) {
                $options[$term->term_id] = $term->name . ' (' . $term->count . ')';
            }
        }

        $this->add_control('folder_id', ['label' => esc_html__('Select Gallery Folder', 'studiofy'), 'type' => Controls_Manager::SELECT, 'options' => $options, 'default' => array_key_first($options)]);
        $this->add_control('mode', ['label' => esc_html__('Display Mode', 'studiofy'), 'type' => Controls_Manager::SELECT, 'options' => ['grid' => 'Public Grid', 'proofing' => 'Client Proofing (Watermarked)'], 'default' => 'grid']);
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => esc_html__('Style', 'studiofy'), 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('columns', ['label' => esc_html__('Columns (Grid)', 'studiofy'), 'type' => Controls_Manager::NUMBER, 'min' => 1, 'max' => 6, 'default' => 3, 'selectors' => ['{{WRAPPER}} .studiofy-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);']]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $folder_id = $settings['folder_id'];
        $mode = $settings['mode'];

        if (!$folder_id) {
            echo '<div class="elementor-alert elementor-alert-warning">Please select a folder.</div>';
            return;
        }

        // Performance: Transient Cache for HTML output
        $cache_key = 'studiofy_gallery_html_' . $folder_id . '_' . $mode;
        $cached_output = get_transient($cache_key);

        if (false !== $cached_output && !is_user_logged_in()) {
             echo $cached_output;
             echo "<script>window.studiofyGallery = window.studiofyGallery || {}; window.studiofyGallery.current_id = {$folder_id};</script>";
             return;
        }

        $args = [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'tax_query' => [['taxonomy' => 'studiofy_folder', 'field' => 'term_id', 'terms' => $folder_id]]
        ];
        
        $query = new \WP_Query($args);
        $watermarker = new \Studiofy\Media\Watermarker();

        ob_start();

        if ($query->have_posts()) {
            echo '<form id="studiofy-proofing-form" class="studiofy-gallery-wrapper">';
            echo '<div class="studiofy-grid">';
            
            while ($query->have_posts()) {
                $query->the_post();
                $id = get_the_ID();
                $src = ($mode === 'proofing') ? $watermarker->apply_watermark($id) : wp_get_attachment_image_url($id, 'medium_large');

                if (!$src) $src = wp_get_attachment_image_url($id, 'medium');

                echo '<div class="studiofy-item">';
                echo '<div class="studiofy-img-wrap">';
                echo '<img src="' . esc_url($src) . '" alt="Photo">';
                
                if ($mode === 'proofing') {
                    echo '<label class="studiofy-select-check">';
                    echo '<input type="checkbox" name="selected_photos[]" value="' . $id . '">';
                    echo '<span class="checkmark dashicons dashicons-heart"></span>';
                    echo '</label>';
                }
                echo '</div></div>';
            }
            echo '</div>';

            if ($mode === 'proofing') {
                echo '<div class="studiofy-proof-actions" style="margin-top:20px; text-align:center;">';
                echo '<button type="submit" class="elementor-button elementor-size-sm">Submit Selections</button>';
                echo '</div>';
            }
            echo '</form>';
        } else {
            echo '<p>No photos found in this folder.</p>';
        }
        
        $output = ob_get_clean();
        
        // Cache for 1 Hour
        set_transient($cache_key, $output, 3600);
        
        echo $output;
        echo "<script>window.studiofyGallery = window.studiofyGallery || {}; window.studiofyGallery.current_id = {$folder_id};</script>";
        
        wp_reset_postdata();
    }
}
