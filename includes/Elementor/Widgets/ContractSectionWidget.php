<?php
/**
 * Contract Section Widget
 * @package Studiofy\Elementor\Widgets
 * @version 2.2.20
 */

declare(strict_types=1);

namespace Studiofy\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class ContractSectionWidget extends Widget_Base {

    public function get_name() { return 'studiofy_contract_section'; }
    public function get_title() { return 'Contract Clause'; }
    public function get_icon() { return 'eicon-document-file'; }
    public function get_categories() { return ['studiofy-category']; }

    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            ['label' => 'Clause Content', 'tab' => Controls_Manager::TAB_CONTENT]
        );

        $this->add_control(
            'clause_title',
            [
                'label' => 'Clause Title',
                'type' => Controls_Manager::TEXT,
                'default' => 'Terms & Conditions',
                'placeholder' => 'Enter clause title',
            ]
        );

        $this->add_control(
            'clause_body',
            [
                'label' => 'Clause Body',
                'type' => Controls_Manager::WYSIWYG,
                'default' => 'Enter your contract terms here.',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        ?>
        <div class="studiofy-contract-clause">
            <h3 class="clause-title"><?php echo esc_html($settings['clause_title']); ?></h3>
            <div class="clause-body"><?php echo $settings['clause_body']; ?></div>
        </div>
        <?php
    }
}
