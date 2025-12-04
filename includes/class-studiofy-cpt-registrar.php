<?php
declare( strict_types=1 );

class Studiofy_CPT_Registrar {
	public function init(): void {
		add_action( 'init', array( $this, 'register_post_types' ) );
	}

	public function register_post_types(): void {
		$slug = 'studiofy-dashboard';

		// Helper to generate labels
		$gen_labels = function( $singular, $plural ) {
			return array(
				'name'               => $plural,
				'singular_name'      => $singular,
				'add_new'            => 'Add New ' . $singular, // The button text
				'add_new_item'       => 'Add New ' . $singular, // The header text
				'edit_item'          => 'Edit ' . $singular,
				'new_item'           => 'New ' . $singular,
				'view_item'          => 'View ' . $singular,
				'search_items'       => 'Search ' . $plural,
				'not_found'          => 'No ' . strtolower( $plural ) . ' found',
				'not_found_in_trash' => 'No ' . strtolower( $plural ) . ' found in Trash',
				'all_items'          => 'All ' . $plural,
			);
		};

		// 1. PROJECTS
		register_post_type( 'studiofy_project', array(
			'labels'       => $gen_labels( 'Project', 'Projects' ),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => $slug,
			'supports'     => array( 'title', 'editor' ), // Title = Name, Editor = Notes
			'menu_icon'    => 'dashicons-portfolio',
		) );

		// 2. LEADS
		register_post_type( 'studiofy_lead', array(
			'labels'       => $gen_labels( 'Lead', 'Leads' ),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => $slug,
			'supports'     => array( 'title' ),
			'menu_icon'    => 'dashicons-filter',
		) );

		// 3. INVOICES
		register_post_type( 'studiofy_invoice', array(
			'labels'       => $gen_labels( 'Invoice', 'Invoices' ),
			'public'       => true,
			'show_ui'      => true,
			'show_in_menu' => $slug,
			'supports'     => array( 'title' ),
			'menu_icon'    => 'dashicons-money-alt',
		) );

		// 4. CONTRACTS
		register_post_type( 'studiofy_contract', array(
			'labels'       => $gen_labels( 'Contract', 'Contracts' ),
			'public'       => true,
			'show_ui'      => true,
			'show_in_menu' => $slug,
			'supports'     => array( 'title', 'editor' ),
			'menu_icon'    => 'dashicons-media-document',
		) );

		// 5. SESSIONS
		register_post_type( 'studiofy_session', array(
			'labels'       => $gen_labels( 'Session', 'Sessions' ),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => $slug,
			'supports'     => array( 'title', 'editor' ),
			'menu_icon'    => 'dashicons-calendar-alt',
		) );

		// 6. GALLERIES
		register_post_type( 'studiofy_gallery', array(
			'labels'       => $gen_labels( 'Gallery', 'Collections' ),
			'public'       => true,
			'show_ui'      => true,
			'show_in_menu' => $slug,
			'supports'     => array( 'title', 'thumbnail' ),
			'menu_icon'    => 'dashicons-images-alt2',
		) );
	}
}
