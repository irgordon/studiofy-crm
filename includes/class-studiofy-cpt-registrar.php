<?php
declare( strict_types=1 );

class Studiofy_CPT_Registrar {
	public function init(): void {
		add_action( 'init', array( $this, 'register_post_types' ) );
	}

	public function register_post_types(): void {
		$slug = 'studiofy-dashboard';
		$types = array(
			'studiofy_project'  => array( 'Project', 'Projects', 'dashicons-portfolio' ),
			'studiofy_lead'     => array( 'Lead', 'Leads', 'dashicons-filter' ),
			'studiofy_invoice'  => array( 'Invoice', 'Invoices', 'dashicons-money-alt' ),
			'studiofy_contract' => array( 'Contract', 'Contracts', 'dashicons-media-document' ),
			'studiofy_session'  => array( 'Session', 'Sessions', 'dashicons-calendar-alt' ),
			'studiofy_gallery'  => array( 'Gallery', 'Galleries', 'dashicons-images-alt2' ),
		);

		foreach ( $types as $key => $data ) {
			register_post_type( $key, array(
				'labels' => array( 'name' => $data[1], 'singular_name' => $data[0] ),
				'public' => in_array( $key, array( 'studiofy_invoice', 'studiofy_contract', 'studiofy_gallery' ) ),
				'show_ui' => true,
				'show_in_menu' => $slug,
				'supports' => array( 'title', 'editor', 'thumbnail' ),
				'menu_icon' => $data[2],
			) );
		}
	}
}
