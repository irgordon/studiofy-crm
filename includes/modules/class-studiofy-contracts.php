<?php
declare( strict_types=1 );

class Studiofy_Contracts {

	public function init(): void {
		add_action( 'template_redirect', array( $this, 'render_print_view' ) );
	}

	public function render_print_view(): void {
		if ( isset( $_GET['print_contract'] ) && is_user_logged_in() ) {
			$post_id = intval( $_GET['print_contract'] );
			$post = get_post( $post_id );
			
			if ( ! $post || 'studiofy_contract' !== $post->post_type ) return;

			// Fetch Data
			$client_id = get_post_meta( $post_id, '_studiofy_client_id', true );
			$clauses   = json_decode( get_post_meta( $post_id, '_studiofy_clauses', true ), true );
			$pay_type  = get_post_meta( $post_id, '_studiofy_payment_type', true );
			$fee       = get_post_meta( $post_id, '_studiofy_contract_fee', true );
			$dep       = get_post_meta( $post_id, '_studiofy_deposit_pct', true );
			$sig       = get_post_meta( $post_id, '_studiofy_signature', true );

			global $wpdb;
			$client = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}studiofy_clients WHERE id=%d", $client_id ) );
			$client_name = $client ? $client->name : '__________________';
			$client_addr = $client ? $client->email . ' | ' . $client->phone : '__________________';

			// Start HTML Output
			?>
			<!DOCTYPE html>
			<html>
			<head>
				<title><?php echo esc_html( $post->post_title ); ?></title>
				<style>
					body { font-family: "Times New Roman", serif; max-width: 800px; margin: 40px auto; padding: 40px; line-height: 1.6; color: #000; background:#fff; }
					h1 { text-align: center; text-transform: uppercase; font-size: 24px; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px; }
					h3 { text-transform: uppercase; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-top: 30px; font-size: 16px; }
					.parties { background: #f9f9f9; padding: 20px; border: 1px solid #eee; margin-bottom: 30px; }
					.meta-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
					.clause { margin-bottom: 20px; }
					.clause-title { font-weight: bold; text-decoration: underline; margin-right: 5px; }
					.print-btn { position: fixed; top: 20px; right: 20px; background: #2271b1; color: #fff; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px; }
					@media print { .print-btn { display: none; } body { margin: 0; padding: 20px; } }
					.sig-area { display: flex; justify-content: space-between; margin-top: 80px; page-break-inside: avoid; }
					.sig-box { width: 45%; border-top: 1px solid #000; padding-top: 10px; position: relative; }
					.sig-img { position: absolute; bottom: 30px; left: 0; max-height: 60px; }
				</style>
			</head>
			<body>
				<button onclick="window.print()" class="print-btn">Print / Save PDF</button>

				<h1><?php echo esc_html( $post->post_title ); ?></h1>

				<div class="parties">
					<p>This Agreement is made on <strong><?php echo get_the_date( 'F j, Y', $post ); ?></strong> between:</p>
					<div class="meta-row">
						<span><strong>Photographer:</strong> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
						<span><strong>Client:</strong> <?php echo esc_html( $client_name ); ?></span>
					</div>
					<div class="meta-row">
						<span>Email: <?php echo esc_html( get_bloginfo( 'admin_email' ) ); ?></span>
						<span>Contact: <?php echo esc_html( $client_addr ); ?></span>
					</div>
				</div>

				<h3>Services & Fees</h3>
				<p>The Client agrees to engage the Photographer for services described as: <strong><?php echo esc_html( $post->post_title ); ?></strong>.</p>
				<ul>
					<li><strong>Payment Structure:</strong> <?php echo esc_html( $pay_type ); ?></li>
					<li><strong>Total Fee:</strong> $<?php echo number_format( (float)$fee, 2 ); ?></li>
					<?php if ( $dep ) : ?>
						<li><strong>Deposit Required:</strong> <?php echo esc_html( $dep ); ?>% due upon signing.</li>
					<?php endif; ?>
				</ul>

				<h3>Terms & Conditions</h3>
				<?php 
				if ( is_array( $clauses ) ) {
					foreach ( $clauses as $i => $c ) {
						echo '<div class="clause">';
						echo '<span class="clause-title">' . esc_html( $i + 1 ) . '. ' . esc_html( $c['title'] ) . '.</span> ';
						echo wp_kses_post( wpautop( $c['body'] ) );
						echo '</div>';
					}
				} else {
					echo '<p>No specific terms added.</p>';
				}
				?>

				<div class="sig-area">
					<div class="sig-box">
						<?php if ( $sig ) echo '<img src="' . esc_url( $sig ) . '" class="sig-img">'; ?>
						<strong>Client Signature</strong><br>
						Date: <?php echo $sig ? get_the_modified_date( 'F j, Y', $post ) : '______________'; ?>
					</div>
					<div class="sig-box">
						<strong>Photographer Signature</strong><br>
						Date: ______________
					</div>
				</div>

			</body>
			</html>
			<?php
			exit;
		}
	}
}
