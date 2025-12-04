<?php
declare( strict_types=1 );
class Studiofy_Gallery {
	public function render_shortcode( array $atts ): string {
		global $post;
		$ids = get_post_meta( $post->ID, '_studiofy_gallery_ids', true );
		if ( empty( $ids ) ) return '<p>No images.</p>';
		$imgs = explode( ',', $ids );
		ob_start();
		echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px">';
		foreach($imgs as $id) echo wp_get_attachment_image($id, 'medium');
		echo '</div>';
		return (string) ob_get_clean();
	}
}
