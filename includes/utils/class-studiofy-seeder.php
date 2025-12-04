<?php
declare( strict_types=1 );

class Studiofy_Seeder {

	public function run_seed(): void {
		// 1. Control Record
		$this->create_record( 'John Q. Doe', 'john.doe@gmail.com', 'Portrait', 'Signed', 500.00 );

		// 2. Loop Records
		$types = array( 'Portrait', 'Wedding', 'Lifestyle', 'Event' );
		for ( $i = 0; $i < 5; $i++ ) {
			$type = $types[ array_rand( $types ) ];
			$name = 'Demo Client ' . rand( 100, 999 );
			$this->create_record( $name, "demo{$i}@example.com", $type, 'Draft', (float) rand( 500, 5000 ) );
		}
	}

	public function purge_demo_data(): void {
		// 1. Delete Demo CPTs
		$cpts = array( 'studiofy_project', 'studiofy_invoice', 'studiofy_contract', 'studiofy_lead', 'studiofy_session' );
		foreach ( $cpts as $pt ) {
			$posts = get_posts( array(
				'post_type'   => $pt,
				'numberposts' => -1,
				'meta_key'    => '_studiofy_is_demo',
				'meta_value'  => '1',
			) );
			foreach ( $posts as $p ) wp_delete_post( $p->ID, true );
		}

		// 2. Delete Demo Clients (Custom Table)
		global $wpdb;
		// In a real app, you'd add a flag column, here we delete by name pattern for safety in this specific seeder context
		$wpdb->query( "DELETE FROM {$wpdb->prefix}studiofy_clients WHERE email LIKE '%@example.com' OR email = 'john.doe@gmail.com'" );
	}

	private function create_record( string $name, string $email, string $type, string $status, float $amount ): void {
		global $wpdb;
		// Insert Client
		$wpdb->insert( $wpdb->prefix . 'studiofy_clients', array( 'name' => $name, 'email' => $email, 'status' => 'active' ) );
		$client_id = $wpdb->insert_id;

		// Create Contract
		$cid = wp_insert_post( array( 'post_title' => "$type Contract - $name", 'post_type' => 'studiofy_contract', 'post_status' => 'publish' ) );
		$this->tag_demo( $cid, $client_id );
		update_post_meta( $cid, '_studiofy_status', $status );
		
		// If signed, add dummy sig
		if ( 'Signed' === $status ) {
			update_post_meta( $cid, '_studiofy_signature', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==' ); // 1x1 pixel for demo
		}

		// Create Project
		$pid = wp_insert_post( array( 'post_title' => "$name Project", 'post_type' => 'studiofy_project', 'post_status' => 'publish' ) );
		$this->tag_demo( $pid, $client_id );
	}

	private function tag_demo( int $post_id, int $client_id ): void {
		update_post_meta( $post_id, '_studiofy_is_demo', '1' );
		update_post_meta( $post_id, '_studiofy_client_id', $client_id );
	}
}
