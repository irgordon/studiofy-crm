<?php
declare( strict_types=1 );

class Studiofy_Metaboxes {
	// ... init/add_boxes/hooks ...

	// 1. CONTRACT STATUS (Locked if Signed)
	public function render_contract_status( WP_Post $post ): void {
		wp_nonce_field( 'studiofy_meta', 'studiofy_nonce' );
		$status = get_post_meta( $post->ID, '_studiofy_status', true );
		$sig    = get_post_meta( $post->ID, '_studiofy_signature', true );
		$is_locked = ( 'Signed' === $status );

		if ( $is_locked ) {
			echo '<div class="notice notice-warning inline"><p><strong>Locked:</strong> Document is signed.</p></div>';
			echo '<input type="hidden" name="studiofy_status" value="Signed">'; // Maintain status
		} else {
			echo '<select name="studiofy_status" class="widefat">';
			foreach(['Draft','Pending','Signed','Cancelled'] as $s) echo '<option '.selected($status, $s, false).'>'.$s.'</option>';
			echo '</select>';
		}

		echo '<hr>';
		
		// Signature Preview / Capture
		if ( $sig ) {
			echo '<p><strong>Signature on file:</strong></p>';
			echo '<img src="'.esc_url($sig).'" style="max-width:100%; border:1px solid #ddd; padding:5px;">';
			if ( ! $is_locked ) {
				echo '<br><button type="button" class="button button-link-delete" id="clear-sig">Clear Signature</button>';
				echo '<input type="hidden" name="studiofy_clear_sig" id="studiofy_clear_sig_input" value="0">';
			}
		} else {
			echo '<button type="button" class="button button-secondary" id="open-sig-modal">Capture Signature</button>';
		}

		// Modal HTML (Hidden by default)
		?>
		<div id="studiofy-sig-modal" style="display:none;">
			<div style="background:#fff; padding:20px; max-width:500px; margin:50px auto; border:1px solid #ccc; text-align:center;">
				<h3>Sign Here</h3>
				<canvas id="admin-sig-pad" style="border:1px dashed #ccc; width:400px; height:200px; touch-action:none;"></canvas>
				<br><br>
				<button type="button" class="button" id="clear-pad">Clear</button>
				<button type="button" class="button button-primary" id="save-pad">Apply Signature</button>
				<button type="button" class="button button-link" onclick="jQuery('#studiofy-sig-modal').fadeOut();">Cancel</button>
			</div>
			<input type="hidden" name="studiofy_admin_signature" id="studiofy_admin_signature">
		</div>
		<script>
		jQuery(document).ready(function($){
			// Open Modal
			$('#open-sig-modal').click(function(){
				$('#studiofy-sig-modal').fadeIn().css({position:'fixed',top:0,left:0,right:0,bottom:0,background:'rgba(0,0,0,0.5)',zIndex:99999});
				var canvas = document.getElementById('admin-sig-pad');
				if(canvas) {
					canvas.width = 400; canvas.height = 200; // Reset size
					window.adminPad = new SignaturePad(canvas);
				}
			});
			// Save
			$('#save-pad').click(function(){
				if(window.adminPad.isEmpty()){ alert('Please sign.'); return; }
				$('#studiofy_admin_signature').val(window.adminPad.toDataURL());
				$('#studiofy-sig-modal').fadeOut();
				$('#publish').click(); // Auto-save post
			});
			// Clear Button (Existing Sig)
			$('#clear-sig').click(function(){
				if(confirm('Delete signature? Document will be unlocked.')) {
					$('#studiofy_clear_sig_input').val('1');
					$('#publish').click();
				}
			});
			// Clear Pad
			$('#clear-pad').click(function(){ window.adminPad.clear(); });
		});
		</script>
		<?php
	}

	public function save( int $post_id ): void {
		if ( ! isset( $_POST['studiofy_nonce'] ) ) return;
		
		// Handle Signature Save
		if ( ! empty( $_POST['studiofy_admin_signature'] ) ) {
			update_post_meta( $post_id, '_studiofy_signature', sanitize_text_field( $_POST['studiofy_admin_signature'] ) );
			update_post_meta( $post_id, '_studiofy_status', 'Signed' ); // Auto-lock
		}
		// Handle Clear
		if ( isset( $_POST['studiofy_clear_sig'] ) && '1' === $_POST['studiofy_clear_sig'] ) {
			delete_post_meta( $post_id, '_studiofy_signature' );
			update_post_meta( $post_id, '_studiofy_status', 'Pending' ); // Unlock
		}
		
		// Save other fields...
	}
}
