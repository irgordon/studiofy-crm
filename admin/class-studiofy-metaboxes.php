<?php
declare( strict_types=1 );
class Studiofy_Metaboxes {
	public function init(): void {
		add_action( 'add_meta_boxes', array( $this, 'add' ) );
		add_action( 'save_post', array( $this, 'save' ) );
	}
	public function add(): void {
		add_meta_box( 'studiofy_proj', 'Project Data', array( $this, 'render_proj' ), 'studiofy_project', 'normal', 'high' );
		add_meta_box( 'studiofy_form', 'Form Builder', array( $this, 'render_form' ), 'studiofy_session', 'normal' );
		add_meta_box( 'studiofy_gal', 'Gallery', array( $this, 'render_gal' ), 'studiofy_gallery', 'normal' );
	}
	public function render_proj( WP_Post $post ): void {
		wp_nonce_field( 'studiofy_meta', 'studiofy_nonce' );
		$type = get_post_meta( $post->ID, '_studiofy_project_type', true );
		echo '<label>Type:</label> <select name="studiofy_project_type" class="widefat">';
		foreach(['Portrait','Corporate','Event'] as $t) echo '<option '.selected($type, $t, false).'>'.$t.'</option>';
		echo '</select>';
	}
	public function render_form( WP_Post $post ): void {
		$json = get_post_meta( $post->ID, '_studiofy_form_schema', true );
		echo '<div id="studiofy-fields-container"></div><button type="button" class="button" id="add-field">Add Field</button>';
		echo '<input type="hidden" name="studiofy_form_schema" id="studiofy_form_schema_input" value="'.esc_attr($json).'">';
	}
	public function render_gal( WP_Post $post ): void {
		$ids = get_post_meta( $post->ID, '_studiofy_gallery_ids', true );
		echo '<input type="hidden" name="studiofy_gallery_ids" id="studiofy_gallery_ids" value="'.esc_attr($ids).'"><button id="studiofy-select-images" class="button">Select Images</button>';
	}
	public function save( int $id ): void {
		if(!isset($_POST['studiofy_nonce']) || !wp_verify_nonce($_POST['studiofy_nonce'], 'studiofy_meta')) return;
		$keys = ['studiofy_project_type', 'studiofy_form_schema', 'studiofy_gallery_ids'];
		foreach($keys as $k) if(isset($_POST[$k])) update_post_meta($id, '_'.$k, sanitize_text_field($_POST[$k]));
	}
}
