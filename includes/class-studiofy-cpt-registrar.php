<?php
declare( strict_types=1 );

class Studiofy_CPT_Registrar {
	public function init(): void {
		add_action( 'init', array( $this, 'register_post_types' ) );
	}

	public function register_post_types(): void {
		$menu_slug = 'studiofy-dashboard';

		register_post_type( 'studiofy_project', array(
			'labels' => array( 'name' => __( 'Projects', 'studiofy-crm' ), 'singular_name' => __( 'Project', 'studiofy-crm' ) ),
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => $menu_slug,
			'supports' => array( 'title', 'editor' ),
			'menu_icon' => 'dashicons-portfolio',
		) );

		register_post_type( 'studiofy_invoice', array(
			'labels' => array( 'name' => __( 'Invoices', 'studiofy-crm' ), 'singular_name' => __( 'Invoice', 'studiofy-crm' ) ),
			'public' => true,
			'show_ui' => true,
			'show_in_menu' => $menu_slug,
			'supports' => array( 'title' ),
			'menu_icon' => 'dashicons-money-alt',
		) );

		register_post_type( 'studiofy_contract', array(
			'labels' => array( 'name' => __( 'Contracts', 'studiofy-crm' ), 'singular_name' => __( 'Contract', 'studiofy-crm' ) ),
			'public' => true,
			'show_ui' => true,
			'show_in_menu' => $menu_slug,
			'supports' => array( 'title', 'editor' ),
			'menu_icon' => 'dashicons-media-document',
		) );
	}
}
