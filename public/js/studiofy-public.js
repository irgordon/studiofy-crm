(function( $ ) {
	'use strict';

	$(function() {
		var canvas = document.getElementById('signature-pad');
		if ( ! canvas ) {
			return;
		}

		// Resize canvas for high-DPI screens
		function resizeCanvas() {
			var ratio =  Math.max(window.devicePixelRatio || 1, 1);
			canvas.width = canvas.offsetWidth * ratio;
			canvas.height = canvas.offsetHeight * ratio;
			canvas.getContext("2d").scale(ratio, ratio);
		}
		window.onresize = resizeCanvas;
		resizeCanvas();

		// Init Library
		var pad = new SignaturePad(canvas);

		$('#save-sig').on('click', function(e) {
			e.preventDefault();
			
			if ( pad.isEmpty() ) {
				alert( studiofy_public_vars.strings.empty_sig );
				return;
			}

			var $btn = $(this);
			$btn.text( studiofy_public_vars.strings.signing ).prop('disabled', true);

			// Standard WP AJAX Call
			$.ajax({
				url: studiofy_public_vars.ajax_url,
				type: 'POST',
				data: {
					action: 'studiofy_submit_signature',
					security: studiofy_public_vars.nonce, // Security Nonce
					id: $('#cid').val(),
					token: $('#ctoken').val(),
					signature: pad.toDataURL()
				},
				success: function( response ) {
					if ( response.success ) {
						alert( studiofy_public_vars.strings.success );
						location.reload();
					} else {
						alert( 'Error: ' + response.data );
						$btn.text( studiofy_public_vars.strings.btn_text ).prop('disabled', false);
					}
				},
				error: function() {
					alert( studiofy_public_vars.strings.error );
					$btn.text( studiofy_public_vars.strings.btn_text ).prop('disabled', false);
				}
			});
		});
	});

})( jQuery );
