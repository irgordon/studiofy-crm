<?php
class Studiofy_Deactivator {
	public static function deactivate() {
		wp_clear_scheduled_hook( 'studiofy_async_generate_invoice' );
		flush_rewrite_rules();
	}
}
