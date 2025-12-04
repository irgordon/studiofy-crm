(function( $ ) {
	'use strict';

	/**
	 * Studiofy Admin Logic
	 */
	$(function() {

		// 1. Unobtrusive "Delete" Confirmation
		// Replaces inline onclick="confirm(...)"
		$(document).on('click', '.studiofy-delete-action', function(e) {
			if ( ! confirm( studiofy_admin_vars.strings.confirm_delete ) ) {
				e.preventDefault();
			}
		});

		// 2. Heartbeat API Integration (Dashboard Auto-Refresh)
		// Only run this on the dashboard page
		if ( $('#studiofy-dashboard-stats').length > 0 ) {

			// Hook into Heartbeat sending
			$(document).on( 'heartbeat-send', function( e, data ) {
				// Add our request to the heartbeat data stack
				data['studiofy_refresh_stats'] = studiofy_admin_vars.nonce;
			});

			// Hook into Heartbeat receiving
			$(document).on( 'heartbeat-tick', function( e, data ) {
				// Check if our data is in the response
				if ( ! data['studiofy_stats'] ) {
					return;
				}

				var stats = data['studiofy_stats'];

				// Update the DOM elements with new numbers
				$('.studiofy-stat-leads').text( stats.leads );
				$('.studiofy-stat-invoices').text( stats.unpaid );
				
				// Optional: Visual cue that data updated
				$('.k-card h3').css('color', '#2271b1').animate({opacity: 1}, 500);
			});
		}

	});

})( jQuery );
