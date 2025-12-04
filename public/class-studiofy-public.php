<?php
class Studiofy_Public {
    private $version;
    public function __construct( $plugin_name, $version ) { $this->version = $version; }

    public function enqueue_scripts() {
        global $post;
        if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'studiofy_contract' ) ) {
            wp_enqueue_script( 'sig-pad', 'https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js', [], '4.0', true );
            wp_enqueue_script( 'studiofy-js', STUDIOFY_URL . 'public/js/studiofy-public.js', ['jquery', 'sig-pad'], $this->version, true );
            wp_localize_script( 'studiofy-js', 'studiofy_vars', ['ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('studiofy_sign')] );
        }
    }

    public function render_contract_shortcode( $atts ) {
        $atts = shortcode_atts( ['id' => 0], $atts, 'studiofy_contract' );
        $id = intval( $atts['id'] );
        $token = isset( $_GET['token'] ) ? sanitize_text_field( $_GET['token'] ) : '';

        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_contracts WHERE id=%d", $id) );

        if ( ! $row ) return '<p>Contract not found.</p>';
        
        // Security: Check token if not admin
        if ( ! current_user_can('manage_studiofy_contracts') && $row->access_token !== $token ) {
            return '<p class="error">Access Denied. Invalid Token.</p>';
        }

        ob_start();
        echo '<div class="contract-wrapper" style="max-width:800px; margin:0 auto;">';
        echo '<div class="contract-content">' . wp_kses_post( $row->content ) . '</div>';
        echo '<hr>';
        echo '<h3>Sign Below</h3>';
        echo '<canvas id="signature-pad" style="border:1px dashed #ccc; width:100%; height:200px;"></canvas>';
        echo '<button id="save-sig" class="button">Agree & Sign</button>';
        echo '<input type="hidden" id="cid" value="'.$id.'">';
        echo '<input type="hidden" id="ctoken" value="'.esc_attr($row->access_token).'">';
        echo '</div>';
        return ob_get_clean();
    }

    public function handle_signature_submission() {
        check_ajax_referer( 'studiofy_sign', 'security' );
        $id = intval( $_POST['id'] );
        $token = sanitize_text_field( $_POST['token'] );
        $sig = $_POST['signature'];
        
        global $wpdb;
        
        // Double check token server side
        $valid = $wpdb->get_var( $wpdb->prepare("SELECT id FROM {$wpdb->prefix}studiofy_contracts WHERE id=%d AND access_token=%s", $id, $token) );
        
        if ( $valid ) {
            $wpdb->update( 
                $wpdb->prefix.'studiofy_contracts', 
                ['signature_data' => $sig, 'status' => 'signed', 'signed_ip' => $_SERVER['REMOTE_ADDR']], 
                ['id' => $id] 
            );
            wp_send_json_success();
        } else {
            wp_send_json_error( 'Invalid Token' );
        }
    }
}
