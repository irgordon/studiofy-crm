<?php
declare( strict_types=1 );

class Studiofy_Seeder {

	public function run_seed(): void {
		global $wpdb;

		// 1. Control Record
		$this->create_single_record( 'John Q. Doe', 'john.doe@gmail.com', '(202) 555-1234', '123 Anywhere St, Arlington, TX 12345', 'Wedding', 'Full Wedding Package', 'Pre-Production', 'In Progress', 'Partial', 2500.00 );

		// 2. Loop Records
		$first_names = array( 'Alice', 'Marcus', 'Elena', 'David', 'Sarah' );
		$last_names  = array( 'Smith', 'Johnson', 'Garcia', 'Martinez', 'Robinson' );
		$types       = array( 'Portrait', 'Wedding', 'Lifestyle', 'Event' );

		for ( $i = 0; $i < 5; $i++ ) {
			$fn    = $first_names[ array_rand( $first_names ) ];
			$ln    = $last_names[ array_rand( $last_names ) ];
			$type  = $types[ array_rand( $types ) ];
			
			$this->create_single_record(
				"$fn $ln",
				strtolower( "$fn.$ln" . rand( 10, 99 ) . "@example.com" ),
				sprintf( '(%03d) %03d-%04d', rand( 200, 999 ), rand( 100, 999 ), rand( 1000, 9999 ) ),
				rand( 100, 9999 ) . " Main St, City, ST",
				$type, "Interested in $type photography.", 'Inquiry', 'New', 'Unpaid', (float) rand( 500, 5000 )
			);
		}
	}

	private function create_single_record( string $name, string $email, string $phone, string $address, string $type, string $note, string $phase, string $status, string $inv_status, float $amount ): void {
		global $wpdb;
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}studiofy_clients WHERE email = %s", $email ) );
		if ( ! $exists ) {
			$wpdb->insert( $wpdb->prefix . 'studiofy_clients', array( 'name' => $name, 'email' => $email, 'phone' => $phone, 'status' => 'active', 'created_at' => current_time( 'mysql' ) ) );
			$client_id = $wpdb->insert_id;
		} else {
			$client_id = $exists;
		}

		// 1. Define Clauses Array (The part you likely pasted incorrectly before)
		$clauses = array(
			array( 'title' => 'Copyright', 'body' => 'The Photographer retains copyright to all images created and will have the right to make reproductions for portfolio, samples, self-promotion, and professional competition.' ),
			array( 'title' => 'Cancellation', 'body' => 'If the Client requests to amend or cancel this agreement 7 or more days before the session date, the deposit shall be applied a mutually agreed upon reschedule date.' ),
			array( 'title' => 'Safe Environment', 'body' => 'The Client agrees to provide a safe environment. If the Photographer feels unsafe, they may end the session immediately.' ),
			array( 'title' => 'Delivery', 'body' => 'Images will be delivered within 4 weeks of the session date via the Studiofy Gallery.' )
		);

		$raw_template = $this->get_wedding_contract_template();
		$replacements = array(
			'[DATE]'               => date( 'F j, Y' ),
			"[PHOTOGRAPHER'S NAME]" => 'Ian R. Gordon (Studiofy)',
			'[MAILING ADDRESS]'    => '123 Studio Way, Photo City, CA',
			"[CLIENT'S NAME]"      => $name,
			'[CLIENT ADDRESS]'     => $address,
			'[TITLE]'              => "$type Shoot for $name",
			'[ADDRESS]'            => 'TBD Location',
			'[START TIME]'         => '10:00 AM',
			'[END TIME]'           => '6:00 PM',
			'[AMOUNT]'             => number_format( $amount, 2 ),
			'[TERMS OF PAYMENT]'   => 'Credit Card or Check',
			'[#]'                  => '50',
			'[ADDITIONAL TERMS & CONDITIONS]' => 'See attached clauses.',
		);

		$content = str_replace( '[MAILING ADDRESS]', $address, $raw_template );
		$content = str_replace( array_keys( $replacements ), array_values( $replacements ), $raw_template );

		// Create Contract
		$contract_id = wp_insert_post( array( 'post_title' => "$type Contract - $name", 'post_content' => $content, 'post_type' => 'studiofy_contract', 'post_status' => 'publish' ) );
		
		// Save Meta
		update_post_meta( $contract_id, '_studiofy_client_id', $client_id );
		update_post_meta( $contract_id, '_studiofy_status', 'Pending' );
		update_post_meta( $contract_id, '_studiofy_clauses', wp_json_encode( $clauses ) );
		update_post_meta( $contract_id, '_studiofy_payment_type', 'Per Job (Flat Fee)' );
		update_post_meta( $contract_id, '_studiofy_contract_fee', $amount );
		update_post_meta( $contract_id, '_studiofy_deposit_pct', '50' );

		if ( $name === 'John Q. Doe' ) {
			update_post_meta( $contract_id, '_studiofy_status', 'Signed' );
			update_post_meta( $contract_id, '_studiofy_signature', 'https://upload.wikimedia.org/wikipedia/commons/e/e4/John_Hancock_Signature.svg' );
		}

		$proj_id = wp_insert_post( array( 'post_title' => "$name Project", 'post_type' => 'studiofy_project', 'post_status' => 'publish' ) );
		update_post_meta( $proj_id, '_studiofy_client_id', $client_id );
		update_post_meta( $proj_id, '_studiofy_contract_id', $contract_id );
	}

	private function get_wedding_contract_template(): string {
		return <<<HTML
		<div class="contract-body">
			<h2 style="text-align:center;">WEDDING PHOTOGRAPHY CONTRACT</h2>
			<p><strong>PARTIES.</strong> This Wedding Photography Contract (“Contract”) made on [DATE] is between:</p>
			<p><strong>Photographer:</strong> [PHOTOGRAPHER'S NAME] with a mailing address of [MAILING ADDRESS], and</p>
			<p><strong>Client:</strong> [CLIENT'S NAME] with a mailing address of [CLIENT ADDRESS].</p>
			<h3>TERMS</h3>
			<p>The Photographer and Client agree as follows:</p>
			<ul>
				<li><strong>Wedding Title:</strong> [TITLE]</li>
				<li><strong>Address:</strong> [ADDRESS]</li>
				<li><strong>Date:</strong> [DATE]</li>
				<li><strong>Start Time:</strong> [START TIME]</li>
				<li><strong>End Time:</strong> [END TIME]</li>
			</ul>
			<p><strong>Fees.</strong> The Client agrees to pay the Photographer a total amount of <strong>$[AMOUNT]</strong> for their services.</p>
			<p><strong>Additional Terms.</strong> [ADDITIONAL TERMS & CONDITIONS]</p>
		</div>
HTML;
	}
}
