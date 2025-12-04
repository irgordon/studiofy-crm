public function append_signature_pad( string $content ): string {
		if ( is_singular( 'studiofy_contract' ) ) {
			$sig = get_post_meta( get_the_ID(), '_studiofy_signature', true );
			
			// Show Signature Image if Signed
			if ( $sig ) {
				$content .= '<div class="studiofy-signed-box" style="border:2px solid #46b450; padding:20px; margin-top:30px; text-align:center; background:#f0f9f0;">';
				$content .= '<h3 style="color:#46b450;">Digitally Signed</h3>';
				$content .= '<img src="' . esc_url( $sig ) . '" alt="Signature" style="max-width:300px;">';
				$content .= '<p><small>Document Locked.</small></p>';
				$content .= '</div>';
			} else {
				// Show Pad
				$content .= '<div id="sig-area" style="margin-top:30px;">';
				$content .= '<p>Please sign below using your mouse or finger:</p>';
				$content .= '<canvas id="signature-pad" style="border:1px dashed #ccc; width:100%; height:200px; touch-action:none;"></canvas>';
				$content .= '<br><button id="clear-sig" class="button">Clear</button> ';
				$content .= '<button id="save-sig" class="button button-primary">Adopt & Sign</button>';
				$content .= '</div>';
			}
		}
		return $content;
	}
