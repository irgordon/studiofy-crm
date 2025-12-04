<?php
declare( strict_types=1 );

class Studiofy_Seeder {

	public function run_seed(): void {
		global $wpdb;

		// 1. Control Record (John Doe)
		$this->create_single_record( 'John Q. Doe', 'john.doe@gmail.com', '(202) 555-1234', '123 Anywhere St, Arlington, TX 12345', 'Portrait', 'Corporate Headshots', 'Pre-Production', 'In Progress', 'Partial', 500.00 );

		// 2. Loop Random Records
		$first_names = array( 'Alice', 'Marcus', 'Elena', 'David', 'Sarah' );
		$last_names  = array( 'Smith', 'Johnson', 'Garcia', 'Martinez', 'Robinson' );
		$types       = array( 'Portrait', 'Wedding', 'Lifestyle', 'Event' );

		for ( $i = 0; $i < 5; $i++ ) {
			$fn    = $first_names[ array_rand( $first_names ) ];
			$ln    = $last_names[ array_rand( $last_names ) ];
			$type  = $types[ array_rand( $types ) ];
			$name  = "$fn $ln";
			$email = strtolower( "$fn.$ln" . rand( 10, 99 ) . "@example.com" );
			$phone = sprintf( '(%03d) %03d-%04d', rand( 200, 999 ), rand( 100, 999 ), rand( 1000, 9999 ) );
			
			$this->create_single_record(
				$name,
				$email,
				$phone,
				rand( 100, 9999 ) . " Main St, City, ST",
				$type,
				"Interested in $type photography.",
				'Inquiry',
				'New',
				'Unpaid',
				(float) rand( 500, 5000 )
			);
		}
	}

	private function create_single_record( string $name, string $email, string $phone, string $address, string $type, string $note, string $phase, string $status, string $inv_status, float $amount ): void {
		global $wpdb;

		// 1. Create/Get Client (DB)
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}studiofy_clients WHERE email = %s", $email ) );
		if ( ! $exists ) {
			$wpdb->insert( $wpdb->prefix . 'studiofy_clients', array( 'name' => $name, 'email' => $email, 'phone' => $phone, 'status' => 'active', 'created_at' => current_time( 'mysql' ) ) );
			$client_id = $wpdb->insert_id;
		} else {
			$client_id = $exists;
		}

		// 2. Create Lead (CPT) - FIX: Explicitly creating Lead CPT
		$lead_id = wp_insert_post( array(
			'post_title'  => $name,
			'post_type'   => 'studiofy_lead',
			'post_status' => 'publish',
		) );
		update_post_meta( $lead_id, '_studiofy_email', $email );
		update_post_meta( $lead_id, '_studiofy_phone', $phone );
		update_post_meta( $lead_id, '_studiofy_address', $address );
		update_post_meta( $lead_id, '_studiofy_session_type', $type );
		update_post_meta( $lead_id, '_studiofy_notes', $note );

		// 3. Create Invoice (CPT)
		$invoice_id = wp_insert_post( array(
			'post_title'  => 'INV-' . strtoupper( uniqid() ),
			'post_type'   => 'studiofy_invoice',
			'post_status' => 'publish',
		) );
		update_post_meta( $invoice_id, '_studiofy_client_id', $client_id );
		update_post_meta( $invoice_id, '_studiofy_amount', $amount );
		update_post_meta( $invoice_id, '_studiofy_status', $inv_status );

		// 4. Create Contract (CPT)
		$contract_id = wp_insert_post( array(
			'post_title'  => "$type Agreement - $name",
			'post_type'   => 'studiofy_contract',
			'post_status' => 'publish',
		) );
		update_post_meta( $contract_id, '_studiofy_client_id', $client_id );
		update_post_meta( $contract_id, '_studiofy_status', 'Draft' );

		// 5. Create Project (CPT) - The Hub
		$proj_id = wp_insert_post( array(
			'post_title'  => "$name - $type Project",
			'post_type'   => 'studiofy_project',
			'post_status' => 'publish',
		) );
		
		// Link Relations
		update_post_meta( $proj_id, '_studiofy_client_id', $client_id );
		update_post_meta( $proj_id, '_studiofy_project_type', $type );
		update_post_meta( $proj_id, '_studiofy_status', $status );
		update_post_meta( $proj_id, '_studiofy_phase', $phase );
		
		// Link Invoice & Contract IDs
		update_post_meta( $proj_id, '_studiofy_linked_invoice_id', $invoice_id ); // WP ID
		update_post_meta( $proj_id, '_studiofy_contract_id', $contract_id );
	}
	
	// ... get_wedding_contract_template() method remains same ...
}
