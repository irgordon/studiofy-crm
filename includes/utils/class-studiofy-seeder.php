// ... inside create_single_record ...

	// 1. Define Clauses Array
	$clauses = array(
		array( 'title' => 'Copyright', 'body' => 'The Photographer retains copyright to all images created and will have the right to make reproductions for portfolio, samples, self-promotion, and professional competition.' ),
		array( 'title' => 'Cancellation', 'body' => 'If the Client requests to amend or cancel this agreement 7 or more days before the session date, the deposit shall be applied a mutually agreed upon reschedule date.' ),
		array( 'title' => 'Safe Environment', 'body' => 'The Client agrees to provide a safe environment. If the Photographer feels unsafe, they may end the session immediately.' ),
		array( 'title' => 'Delivery', 'body' => 'Images will be delivered within 4 weeks of the session date via the Studiofy Gallery.' )
	);

	// 2. Create Contract Post
	$contract_id = wp_insert_post( array(
		'post_title'   => "$type Agreement - $name",
		'post_type'    => 'studiofy_contract',
		'post_status'  => 'publish',
	) );

	// 3. Save Meta (JSON)
	update_post_meta( $contract_id, '_studiofy_clauses', wp_json_encode( $clauses ) );
	update_post_meta( $contract_id, '_studiofy_payment_type', 'Per Job (Flat Fee)' );
	update_post_meta( $contract_id, '_studiofy_contract_fee', $amount );
	update_post_meta( $contract_id, '_studiofy_deposit_pct', '50' );
	update_post_meta( $contract_id, '_studiofy_client_id', $client_id );
