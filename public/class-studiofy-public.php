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

        if ( ! $row || ( ! current_user_can('manage_options') && $row->access_token !== $token ) ) {
            return '<p class="error">Access Denied.</p>';
        }

        ob_start();
        echo '<div class="contract-content">' . wp_kses_post( $row->content ) . '</div>';
        echo '<canvas id="signature-pad"></canvas><button id="save-sig">Sign</button>';
        echo '<input type="hidden" id="cid" value="'.$id.'"><input type="hidden" id="ctoken" value="'.esc_attr($row->access_token).'">';
        return ob_get_clean();
    }

    public function handle_signature_submission() {
        check_ajax_referer( 'studiofy_sign', 'security' );
        $id = intval( $_POST['id'] );
        $sig = $_POST['signature'];
        
        global $wpdb;
        $wpdb->update( 
            $wpdb->prefix.'studiofy_contracts', 
            ['signature_data' => $sig, 'status' => 'signed', 'signed_ip' => $_SERVER['REMOTE_ADDR']], 
            ['id' => $id] 
        );
        wp_send_json_success();
    }
}
